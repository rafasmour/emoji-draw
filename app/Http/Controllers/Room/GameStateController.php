<?php

namespace App\Http\Controllers\Room;

use App\DataObjects\ChatMessage as ChatMessageDTO;
use App\DataObjects\RoomStatus;
use App\DataObjects\RoomUser;
use App\DataObjects\UserStats;
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
        if ($room->users->count() < 2) {
            return response()->json(['message' => 'not enough users'], 403);
        }
        if ($room->status->started) {
            return response()->json(['message' => 'game already started'], 403);
        }
        if ($user->id !== $room->owner) {
            return response()->json(['message' => 'unauthorized'], 403);
        }

        $room->status = new RoomStatus(
            round: 0,
            time: Carbon::now()->addSeconds($room->settings->timeLimit)->toDateTimeString('second'),
            term: 'test',
            started: true,
        );

        $message = new ChatMessageDTO(
            user_id: $user->id,
            user_name: $user->name,
            message: 'started game',
        );
        $room->chat = $room->chat->push($message);

        $randomIndex = fake()->numberBetween(0, $room->users->count() - 1);
        $room->artist = $room->users[$randomIndex]->id;

        $room->save();
        RoundHandler::dispatch($room)->delay(now()->addSeconds($room->settings->timeLimit));
        broadcast(new StartGame($room));

        return response()->redirectToRoute('room.game', $room);
    }

    public function stop(Request $request, Room $room)
    {
        if ($request->user->id !== $room->owner) {
            return response()->json(['message' => 'unauthorized'], 403);
        }

        $room->status = new RoomStatus(
            round: 0,
            time: 0,
            term: $room->status->term,
            started: false,
        );

        $message = new ChatMessageDTO(
            user_id: $request->user->id,
            user_name: $request->user->name,
            message: 'stopped game',
        );

        $room->chat = $room->chat->push($message);

        $users = $room->users->map(fn (RoomUser $user) => new RoomUser(
            id: $user->id,
            name: $user->name,
            score: $user->score,
            guesses: 0,
            guessed: false,
            correct_guesses: 0,
            room_token: $user->room_token,
        ));

        $room->canvas = collect([]);
        $room->users = $users;
        $room->save();
        broadcast(new StopGame($room));
        broadcast(new ChatMessage($room, $message));

        return response()->json(['message' => 'game stopped']);
    }

    public function finish(Room $room)
    {
        $room->users->each(function (RoomUser $roomUser) {
            $user = User::find($roomUser->id);
            if (! $user) {
                return;
            }

            $currentStats = $user->stats->first();
            if ($currentStats) {
                $user->stats = collect([
                    new UserStats(
                        guesses: (string) ((int) $currentStats->guesses + $roomUser->guesses),
                        correct_guesses: (string) ((int) $currentStats->correct_guesses + $roomUser->correct_guesses),
                    ),
                ]);
            }
            $user->save();
        });

        $room->status = new RoomStatus(
            round: 0,
            time: 0,
            term: '',
            started: false,
        );

        $message = new ChatMessageDTO(
            user_id: '1',
            user_name: 'System',
            message: 'Game Finished!',
        );

        $room->chat = $room->chat->push($message);
        $room->save();
        broadcast(new GameOver($room));
        broadcast(new ChatMessage($room, $message));
    }
}
