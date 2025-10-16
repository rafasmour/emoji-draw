<?php

namespace App\Http\Controllers\Room;

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

    static function randomOwner(Room $room)
    {
        $user_ids = array_map(fn($usr) => $usr['id'], $room->users);
        $randomIndex = fake()->numberBetween(0, count($user_ids) - 1);
        $old_owner = User::find($room->owner);
        $room->owner = $user_ids[$randomIndex];
        $new_owner = User::find($room->owner);

        $chatMessages = $room->chat ?? [];
        $message = [
            'user_id' => $new_owner->getKey(),
            'user' => $new_owner->name,
            'message' => "Owner left the new owner is {$new_owner->name}",
        ];
        $chatMessages[] = $message;
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
        if (!$this->userInRoom($validated['user_id'], $room)) {
            return response()->json(['message' => 'user not in room'], 403);
        }
        $room->owner = $validated['user_id'];
        $new_owner = User::find($validated['user_id']);
        $chatMessages = $room->chat ?? [];
        $message = [
            'user_id' => $user->id,
            'user' => $user->name,
            'message' => "Changed Owner to {$new_owner->name}",
        ];
        $chatMessages[] = $message;
        $room->save();
        $room->refresh();
        broadcast(new ChangeOwner($room, $new_owner));
        broadcast(new ChatMessage($room, $message));
    }
}
