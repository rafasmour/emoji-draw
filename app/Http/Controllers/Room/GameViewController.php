<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GameViewController extends Controller
{
    public function index(Request $request, Room $room): Response|RedirectResponse
    {
        if (! $room->status->started) {
            return redirect()->route('room.lobby', $room);
        }

        $status = $room->status;
        $isArtist = (string) $request->user()->getKey() === (string) $room->artist;

        return Inertia::render('room/game', [
            'is_artist' => $isArtist,
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
                    'term' => $isArtist ? $status->term : null,
                    'guesses' => $status->guesses,
                ],
                'artist' => $room->artist,
            ],
        ]);
    }
}
