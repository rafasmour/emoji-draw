<?php

namespace App\Http\Controllers\Room;

use App\Events\ChangeOwner;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class ChangeOwnerController extends Controller
{
    public function changeOwner(Request $request)
    {
        $validated = $request->validate([
            'owner' => ['required', 'exists:users,id'],
            'roomId' => ['required', 'exists:rooms,id'],
            'newOwner' => ['required', 'exists:users,id'],
        ]);
        $room = Room::get()->findOrFail('id', $validated["roomId"]);
        if ($validated['owner'] !== $room->owner) {
            return response()->json(['message' => 'You are not the owner of this room'], 403);
        }
        $roomUsers = $room->users;
        $newOwner = array_filter($roomUsers, function ($user) use ($validated) {
            return $user->id === $validated['newOwner'];
        })[0];
        if (!$newOwner) {
            return response()->json(['message' => 'The new owner does not exist in this room'], 404);
        }
        $room->update(['owner' => $newOwner->id]);
        $room->save();
        event(new ChangeOwner($room, $newOwner->id));
        return response()->json(['message' => 'Owner changed successfully']);
    }
}
