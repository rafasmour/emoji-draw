<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GameViewController extends Controller
{
    public function index(Request $request, Room $room)
    {
        if (! $room->status->started) {
            return redirect()->route('room.lobby', $room);
        }
        $status = $room->status;

        return Inertia::render('room/game', [
            'room' => [
                'id' => $room->getKey(),
                'name' => $room->name,
                'settings' => $room->settings,
                'users' => $room->users,
                'chat' => $room->chat,
                'owner' => $room->owner,
                'canvas' => $room->canvas,
                'status' => [
                    'started' => $status->started,
                    'round' => $status->round,
                    'time' => Carbon::now()->diffInSeconds($status->time),
                    'term' => $status->term,
                    'guesses' => $status->guesses,
                ],
                'artist' => $room->artist,
            ],
        ]);
    }
}
