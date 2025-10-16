<?php

namespace App\Http\Controllers\Room;

use App\Events\CanvasStroke;
use App\Events\ChatMessage;
use App\Events\CorrectGuess;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Rules\EmojiOnly;
use Illuminate\Http\Request;
use Validator;

class GameActionController extends Controller
{
    public function canvas(Request $request, Room $room)
    {
        return $room->canvas;
    }

    public function stroke(Request $request, Room $room)
    {
        $validated = Validator::make($request->all(), [
            'x' => ['required', 'integer', 'min:0', 'max:10000'],
            'y' => ['required', 'integer', 'min:0', 'max:10000'],
            'emoji' => ['required', 'max:5', new EmojiOnly],
            'size' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);
        if ($validated->fails()) {
            return response()->json($validated->errors()->toArray(), 400);
        }
        $userId = $request->user()->id;
        if ($room->artist !== $userId) {
            return response()->json(['message' => 'not artist'], 403);
        }
        $canvas = $room->canvas ?? [];
        $canvas[] = $validated->validated();
        $room->canvas = $canvas;
        $room->save();
        $room->refresh();

        broadcast(new CanvasStroke($room, $request->toArray()))->toOthers();
    }

    public function guess(Request $request, Room $room)
    {
        $user = $request->user();
        $validated = $request->validate([
            'guess' => ['required', 'string', 'min:1', 'max:255'],
        ]);
        if($user->getKey() === $room->artist) {
            return response()->json(['message' => "Artist Can't guess"], 403);
        }
        $guess = $validated['guess'];
        $userStats = array_values(array_filter($room->users, fn($usr) => $usr['id'] === $request->user()->id))[0];
        if($userStats['guessed']) {
            return response()->json(['message' => 'already guessed'], 403);
        };
//        dd($userStats['guessed']);
        $userStats['guesses'] += 1;
        $roomStatus = $room->status;
        if ($guess === $roomStatus['term']) {
            $userStats['correct_guesses'] = $userStats['correct_guesses'] + 1;
            $userStats['guessed'] = true;
            $message = [
                'user_id' => $user->id,
                'user' => $user->name,
                'message' => 'Guessed Correctly!',
            ];
            $chat = $room->chat ?? [];
            $chat[] = $message;
            $room->chat = $chat;
            $roomUsers = $room->users;
            $roomUsers = array_map(fn($usr) => $usr['id'] === $user->id ? $userStats : $usr, $roomUsers);
            $room->users = $roomUsers;
            broadcast(new ChatMessage($room, $message));
        }
        $room->status = $roomStatus;
        $room->save();
        new CorrectGuess($request->user(), $room);
    }

}
