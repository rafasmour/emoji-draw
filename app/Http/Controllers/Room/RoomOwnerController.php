<?php

namespace App\Http\Controllers\Room;

use App\Events\ChangeOwner;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use App\UserInRoom;
use Illuminate\Http\Request;

class RoomOwnerController extends Controller
{
    use UserInRoom;
    public function changeOwner(Request $request, Room $room)
    {
        if ($request->user->id !== $room->owner)
        {
            return response()->json(['message' => 'unauthorized'], 403);
        }

        $validated = $request->validate([
            'user_id' => ['required', 'string', 'exists:users,id'],
        ]);
        if(!$this->userInRoom($validated['user_id'], $room))
        {
            return response()->json(['message' => 'user not in room'], 403);
        }
        $room->owner = $validated['user_id'];
        $room->save();
        $room->refresh();
        $new_owner = User::find($validated['user_id'])->first();
        $old_owner = $request->user();
        broadcast(new ChangeOwner($room, $new_owner, $old_owner));
        return response()->json(['message' => 'owner changed']);
    }
    public function randomOwner(Room $room)
    {
        $user_ids = array_map(fn($usr) => $usr['id'], $room->users);
        $randomIndex = fake()->numberBetween(0, count($user_ids) - 1);
        $old_owner = User::find($room->owner);
        $room->owner = $user_ids[$randomIndex];
        $room->save();
        $room->refresh();
        broadcast(new ChangeOwner($room, User::find($room->owner), $old_owner));
    }
}
