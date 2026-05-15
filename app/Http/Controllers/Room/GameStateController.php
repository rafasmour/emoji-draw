<?php

namespace App\Http\Controllers\Room;

use App\DataObjects\RoomUser;
use App\Events\ChatMessage;
use App\Events\GameOver;
use App\Events\StartGame;
use App\Events\StopGame;
use App\Http\Controllers\Controller;
use App\Jobs\RoundHandler;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        $roomSettings = $room->settings;
        $roomStatus = $room->status ?? [];
        $room->status = [
            ...$roomStatus,
            'started' => false,
            'round' => 0,
            'time' => Carbon::now()->addSeconds($roomSettings->timeLimit)->toDateTimeString('second'),
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
        $userIds = $room->users->pluck('id')->unique()->values();
        $randomIndex = fake()->numberBetween(0, $userIds->count() - 1);
        $room->artist = $userIds->get($randomIndex);
        $roomStatus = $room->status ?? [];
        $roomStatus['term'] = 'test';
        $room->status = $roomStatus;
        $roomSettings = $room->settings;
        $room->save();
        RoundHandler::dispatch($room)->delay(now()->addSeconds($roomSettings->timeLimit));
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
        $room->canvas = [];
        $room->users = $room->users->map(fn (RoomUser $user) => new RoomUser(
            id: $user->id,
            name: $user->name,
            score: $user->score,
            guesses: 0,
            correct_guesses: 0,
            guessed: false,
            room_token: $user->room_token,
        ));
        $room->save();
        broadcast(new StopGame($room));
        broadcast(new ChatMessage($room, $message));

        return response()->json(['message' => 'game stopped']);
    }

    public function finish(Room $room)
    {
        $room->users = $room->users->map(function (RoomUser $userStats) {
            $user = User::find($userStats->id);
            if ($user) {
                $user->guess_count = $userStats->guesses;
                if ($userStats->guesses > 0) {
                    $user->guess_accuracy = $userStats->correct_guesses / $userStats->guesses;
                }
                $user->save();
            }

            return new RoomUser(
                id: $userStats->id,
                name: $userStats->name,
                score: $userStats->score,
                guesses: 0,
                correct_guesses: 0,
                guessed: false,
                room_token: $userStats->room_token,
            );
        });
        $room->status = [
            'round' => 0,
            'time' => 0,
            'term' => '',
            'started' => false,
            'guesses' => 0,
        ];
        $message = [
            'user_id' => '1',
            'user' => 'System',
            'message' => 'Game Finished!',
        ];
        $chat = $room->chat ?? [];
        $chat[] = $message;
        $room->chat = $chat;
        $room->save();
        broadcast(new GameOver($room));
        broadcast(new ChatMessage($room, $message));
    }
}
