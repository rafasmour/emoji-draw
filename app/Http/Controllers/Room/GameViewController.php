<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GameViewController extends Controller
{
    public function index(Request $request, Room $room)
    {
        if (!$room->status['started']) {
            return redirect()->route('room.lobby', $room);
        }
//        dd($room->canvas);
        $roomSettings = $room->settings;
        return Inertia::render("room/game", [
            'room' => [
                'id' => $room->getKey(),
                'name' => $room->name,
                'settings' => $roomSettings,
                'users' => $room->users,
                'chat' => $room->chat,
                'owner' => $room->owner,
                'canvas' => $room->canvas,
                'status' => $room->status,
                'artist' => $room->artist,
            ],
        ]);
    }
}
