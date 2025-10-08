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
        if(!$room->started) {
            return redirect()->route('room.lobby', $room);
        }
        return Inertia::render("room/game");
    }
}
