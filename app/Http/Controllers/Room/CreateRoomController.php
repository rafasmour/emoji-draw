<?php

namespace App\Http\Controllers\Room;

use App\DataObjects\RoomSettings;
use App\DataObjects\RoomStatus;
use App\DataObjects\RoomUser;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class CreateRoomController extends Controller
{
    public function __construct(
        private Room $room,
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:1', 'max:255', 'unique:rooms,name'],
        ]);
        $owner = $request->user();
        $room = $this->room->create([
            'name' => $validated['name'],
            'owner' => $owner->id,
            'users' => collect([
                new RoomUser(
                    $owner->id,
                    $owner->name,
                    0,
                    0,
                    false,
                    0,
                ),
            ]),
            'settings' => new RoomSettings,
            'chat' => [],
            'canvas' => [],
            'status' => new RoomStatus,

        ]);
        $room->save();

        return redirect()->route('room.lobby', $room);
    }
}
