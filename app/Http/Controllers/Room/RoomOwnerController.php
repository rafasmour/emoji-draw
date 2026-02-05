<?php

namespace App\Http\Controllers\Room;

use App\DataObjects\ChatMessage as ChatMessageDTO;
use App\Events\ChangeOwner;
use App\Events\ChatMessage;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use App\UserInRoom;
use Illuminate\Http\Request;

class RoomOwnerController extends Controller
{
    use UserInRoom;

    public static function randomOwner(Room $room)
    {
        $userIds = $room->users->pluck('id');
        $randomIndex = fake()->numberBetween(0, $userIds->count() - 1);
        $old_owner = User::find($room->owner);
        $room->owner = $userIds[$randomIndex];
        $new_owner = User::find($room->owner);

        $message = new ChatMessageDTO(
            user_id: $new_owner->getKey(),
            user_name: $new_owner->name,
            message: "Owner left the new owner is {$new_owner->name}",
        );

        $room->chat = $room->chat->push($message);
        $room->save();
        $room->refresh();

        broadcast(new ChangeOwner($room, $new_owner));
        broadcast(new ChatMessage($room, $message));
    }

    public function changeOwner(Request $request, Room $room)
    {
        $user = $request->user();
        if ($user->getKey() !== $room->owner) {
            return response()->json(['message' => 'unauthorized'], 403);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'string', 'exists:users,id'],
        ]);

        if (! $this->userInRoom($validated['user_id'], $room)) {
            return response()->json(['message' => 'user not in room'], 403);
        }

        $room->owner = $validated['user_id'];
        $new_owner = User::find($validated['user_id']);

        $message = new ChatMessageDTO(
            user_id: $user->id,
            user_name: $user->name,
            message: "Changed Owner to {$new_owner->name}",
        );

        $room->chat = $room->chat->push($message);
        $room->save();
        $room->refresh();

        broadcast(new ChangeOwner($room, $new_owner));
        broadcast(new ChatMessage($room, $message));
    }
}
