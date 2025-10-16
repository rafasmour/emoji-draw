<?php

namespace App\Http\Controllers\Room;

use App\Events\RoomDestroyed;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class DestroyRoomController extends Controller
{
    public function destroy(Request $request, Room $room)
    {
        if (count($room->users) === 0 || $request->user()->id === $room->owner) {
            broadcast(new RoomDestroyed($room));
            $room->delete();
            return response()->redirectToRoute('room.rooms');
        }
        return response()->json(['message' => 'unauthorized'], 403);
    }
}
