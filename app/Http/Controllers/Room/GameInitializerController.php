<?php

namespace App\Http\Controllers\Room;

use App\Events\GameOver;
use App\Events\StartGame;
use App\Events\StopGame;
use App\Http\Controllers\Controller;
use App\Jobs\RoundHandler;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schedule;

class GameInitializerController extends Controller
{
    public function start(Request $request, Room $room)
    {
        if ($request->user->id !== $room->owner) {
            return response()->json(['message' => 'unauthorized'], 403);
        }
        $room->status = [
            'round' => 0,
            'time' => $room->settings['timeLimit'],
        ];
        $room->chat[] = [
            'user_id' => $request->user->id,
            'user_name' => $request->user->name,
            'message' => 'started game',
        ];
        $room->started = true;
        $room->save();
        broadcast(new StartGame($room));
        Schedule::job(new RoundHandler($room))->withoutOverlapping()->everyMinute();
        return response()->json(['message' => 'game started']);
    }

    public function stop()
    {
        if ($request->user->id !== $room->owner) {
            return response()->json(['message' => 'unauthorized'], 403);
        }
        $room->status = [
            'round' => 0,
            'time' => 0,
        ];
        $room->chat[] = [
            'user_id' => $request->user->id,
            'user_name' => $request->user->name,
            'message' => 'stopped game',
        ];
        $room->save();
        broadcast(new StopGame($room));
        return response()->json(['message' => 'game stopped']);
    }

    public function finish(Room $room)
    {
        $roomUserStats = $room->users;
        foreach($roomUserStats as $userStats) {
            $user = User::find($userStats['id']);
            $user->guesses += $userStats['correct_guesses'];
            $user->guess_count = $userStats['guesses'];
            $user->guess_accuracy = $userStats['correct_guesses'] / $userStats['guesses'];
            $user->save();
            $userStats = [
                ...$userStats,
                'guesses' => 0,
                'correct_guesses' => 0,
                'drawings_guessed' => 0,
            ];
        }
        $room->status = [
            'round' => 0,
            'time' => 0,
        ];
        $room->chat[] = [
            'user_id' => $request->user->id,
            'user_name' => $request->user->name,
            'message' => 'stopped game',
        ];
        $room->save();
        broadcast(new GameOver($room));
    }

}
