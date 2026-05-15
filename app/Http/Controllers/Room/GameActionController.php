<?php

namespace App\Http\Controllers\Room;

use App\DataObjects\RoomUser;
use App\Events\CanvasStroke;
use App\Events\ChatMessage;
use App\Events\CorrectGuess;
use App\Http\Controllers\Controller;
use App\Jobs\RoundHandler;
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
        if ($user->getKey() === $room->artist) {
            return response()->json(['message' => "Artist Can't guess"], 403);
        }
        $guess = $validated['guess'];
        $correct = $guess === $room->status->term;
        $userStats = $room->users->firstWhere('id', $user->id);
        if ($userStats->guessed) {
            return response()->json(['message' => 'already guessed'], 403);
        }
        $userStats = new RoomUser(
            id: $userStats->id,
            name: $userStats->name,
            score: $userStats->score,
            guesses: $userStats->guesses + 1,
            correct_guesses: $userStats->correct_guesses,
            guessed: $userStats->guessed,
            room_token: $userStats->room_token,
        );
        if ($correct) {
            $userStats = new RoomUser(
                id: $userStats->id,
                name: $userStats->name,
                score: $userStats->score,
                guesses: $userStats->guesses,
                correct_guesses: $userStats->correct_guesses + 1,
                guessed: true,
                room_token: $userStats->room_token,
            );
            $message = [
                'user_id' => $user->id,
                'user' => $user->name,
                'message' => 'Guessed Correctly!',
            ];
            $chat = $room->chat ?? [];
            $chat[] = $message;
            $room->chat = $chat;
            $room->users = $room->users->map(fn (RoomUser $usr) => $usr->id === $user->id ? $userStats : $usr);
            broadcast(new ChatMessage($room, $message));
        } else {
            $message = [
                'user_id' => $user->id,
                'user' => $user->name,
                'message' => $validated['guess'],
            ];
            $chat = $room->chat ?? [];
            $chat[] = $message;
            $room->chat = $chat;
            broadcast(new ChatMessage($room, $message));
        }
        $room->save();
        broadcast(new CorrectGuess($user, $room));

        if ($correct) {
            $nonArtistUsers = $room->users->filter(fn (RoomUser $u) => $u->id !== $room->artist);
            if ($nonArtistUsers->every(fn (RoomUser $u) => $u->guessed)) {
                RoundHandler::dispatch($room, $room->status->round);
            }
        }
    }
}
