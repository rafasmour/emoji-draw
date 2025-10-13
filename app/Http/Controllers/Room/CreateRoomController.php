<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class CreateRoomController extends Controller
{
    public function __construct(
        private Room $room,
    )
    {}
    public function index(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:1', 'max:255', 'unique:rooms,name'],
        ]);
        $owner = $request->user();
        $room = $this->room->create([
            'name' => $validated['name'],
            'owner' => $owner->id,
            'users' => [
                [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'score' => 0,
                    'guesses' => 0,
                    'correct_guesses' => 0,
                    'drawings_guessed' => 0,
                ]
            ],
            'settings' => [
                'cap' => 10,
                'public' => true,
                'categories' => [],
                'difficulty' => 'easy',
                'language' => 'EN',
                'timeLimit' => 60,
            ],
            'chat' => [],
            'canvasStrokes' => [],
            'status' => [
                'round' => 0,
                'time' => 0,
                'term' => '',
                'started' => false,
            ],

        ]);
        $room->save();
        return redirect()->route('room.lobby', $room);
    }
}
