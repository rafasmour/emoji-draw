<?php

namespace App\Http\Controllers\Room;

use App\Events\CanvasStroke;
use App\Events\CorrectGuess;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Rules\EmojiOnly;
use Illuminate\Http\Request;

class GameActionController extends Controller
{
    public function canvas(Request $request, Room $room)
    {
        return $room->canvas;
    }

    public function stroke(Request $request, Room $room)
    {
        broadcast(new CanvasStroke($room, $request->toArray()))->toOthers();
        $validated = $request->validate([
            'x' => ['required', 'integer', 'min:0', 'max:1000'],
            'y' => ['required', 'integer', 'min:0', 'max:1000'],
            'emoji' => ['required', 'max:1',new EmojiOnly],
            'size' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        dd($validated);
        $userId = $request->user()->id;
        if($room->artist !== $userId) {
            return response()->json(['message' => 'not artist'], 403);
        }
        $room->canvas[] = [
            ...$validated,
        ];
        $room->save();
        $room->refresh();

        return response()->json(['success' => true], 200);
    }

    public function guess(Request $request, Room $room)
    {
        $validated = $request->validate([
            'guess' => ['required', 'string', 'min:1', 'max:255'],
        ]);
        $guess = $validated['guess'];
        $userStats = array_filter($room->users, fn($usr) => $usr['id'] === $request->user()->id)[0];
        $userStats['guesses']++;
        if($guess === $room->status->term) {
            $userStats['correct_guesses'] = $userStats['correct_guesses'] + 1;
        }
        $room->save();
        new CorrectGuess($request->user(), $room);

    }

}
