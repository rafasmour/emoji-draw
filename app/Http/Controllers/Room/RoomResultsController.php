<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoomResultsController extends Controller
{
    public function index(Request $request, Room $room): mixed
    {
        if ($room->status->started) {
            return redirect()->route('room.game', $room);
        }

        return Inertia::render('room/results', [
            'room' => [
                'id' => $room->getKey(),
                'name' => $room->name,
                'owner' => $room->owner,
                'users' => $room->users->sortByDesc('score')->values(),
            ],
        ]);
    }
}
