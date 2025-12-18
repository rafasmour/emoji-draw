<?php

namespace App\Http\Controllers\Room;

use App\Events\ChatMessage;
use App\Events\GameOver;
use App\Events\StartGame;
use App\Events\StopGame;
use App\Http\Controllers\Controller;
use App\Jobs\RoundHandler;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schedule;

class GameStateController extends Controller
{
    public function start(Request $request, Room $room)
    {
        $user = $request->user();
        if (count($room->users) < 2) {
            return response()->json(['message' => 'not enough users'], 403);
        }
        if ($room->status['started']) {
            return response()->json(['message' => 'game already started'], 403);
        }
        if ($user->id !== $room->owner) {
            return response()->json(['message' => 'unauthorized'], 403);
        }
        $roomSettings = $room->settings ?? [];
        $roomStatus = $room->status ?? [];
        $room->status = [
            ...$roomStatus,
            'round' => 0,
            'time' => Carbon::now()->addSeconds($roomSettings['timeLimit'])->toDateTimeString('second'),
        ];
        $roomChat = $room->chat ?? [];
        $message = [
            'user_id' => $user->id,
            'user' => $user->name,
            'message' => 'started game',
        ];
        $roomChat[] = $message;
        $room->chat = $roomChat;
        $roomStatus = $room->status;
        $roomStatus['started'] = true;
        $room->status = $roomStatus;
        $roomUsers = $room->users;
        $roomUsers = array_map(fn($u) => $u['id'], $roomUsers);
        $roomUsers = array_values(array_unique($roomUsers));
        $randomIndex = fake()->numberBetween(0, count($roomUsers) - 1);
        $room->artist = $roomUsers[$randomIndex];
        $roomStatus = $room->status ?? [];
        $roomStatus['term'] = 'test';
        $room->status = $roomStatus;
        $roomSettings = $room->settings ?? [];
        $room->save();
        RoundHandler::dispatch($room)->delay(now()->addSeconds($roomSettings['timeLimit']));
        broadcast(new StartGame($room));
        return response()->redirectToRoute('room.game', $room);
    }

    public function stop(Request $request, Room $room)
    {
        if ($request->user->id !== $room->owner) {
            return response()->json(['message' => 'unauthorized'], 403);
        }
        $room->status = [
            'round' => 0,
            'time' => 0,
        ];
        $roomChat = $room->chat ?? [];
        $message = [
            'user_id' => $request->user->id,
            'user' => $request->user->name,
            'message' => 'stopped game',
        ];
        $roomChat[] = $message;
        $room->chat = $roomChat;
        $users = $room->users;
        foreach ($users as $user) {
            $user = [
                ...$user,
                'guesses' => 0,
                'correct_guesses' => 0,
                'drawings_guessed' => 0,
            ];
        }
        $room->canvas = [];
        $room->users = $users;
        $room->save();
        broadcast(new StopGame($room));
        broadcast(new ChatMessage($room, $message));
        return response()->json(['message' => 'game stopped']);
    }

    public function finish(Room $room)
    {
        $roomUserStats = $room->users;
        foreach ($roomUserStats as $userStats) {
            $user = User::find($userStats['id']);
            $user->guesses += $userStats['correct_guesses'];
            $user->guess_count = $userStats['guesses'];
            if($userStats['guesses'] > 0) {
                $user->guess_accuracy = $userStats['correct_guesses'] / $userStats['guesses'];
            }
            $user->save();
            $userStats = [
                ...$userStats,
                'guesses' => 0,
                'correct_guesses' => 0,
                'drawings_guessed' => 0,
            ];
        }
        $roomStatus = $room->status;
        $room->status = [
            ...$roomStatus,
            'round' => 0,
            'time' => 0,
        ];
        $message = [
            'user_id' => '1',
            'user' => 'System',
            'message' => 'Game Finished!'
        ];
        $chat = $room->chat ?? [];
        $chat[] = $message;
        $room->chat = $chat;
        $room->save();
        broadcast(new GameOver($room));
        broadcast(new ChatMessage($room, $message));
    }

}
