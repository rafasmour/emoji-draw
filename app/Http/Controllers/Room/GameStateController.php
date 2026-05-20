<?php

namespace App\Http\Controllers\Room;

use App\Http\Contracts\GameServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GameStateController extends Controller
{
    public function __construct(
        private GameServiceInterface $gameService,
    ) {}

    public function start(Request $request, Room $room)
    {
        try {
            $this->gameService->start($request->user(), $room);
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
        if ($user->id !== $room->owner) {
            return response()->json(['message' => 'unauthorized'], 403);
        }
        $roomSettings = $room->settings;
        $roomChat = $room->chat ?? [];
        $message = [
            'user_id' => $user->id,
            'user' => $user->name,
            'message' => 'started game',
        ];
        $roomChat[] = $message;
        $room->chat = $roomChat;
        $room->users = $room->users->map(fn (RoomUser $u) => new RoomUser(
            id: $u->id,
            name: $u->name,
            score: 0,
            guesses: 0,
            correct_guesses: 0,
            guessed: false,
            room_token: $u->room_token,
        ));
        $userIds = $room->users->pluck('id')->unique()->values();
        $room->artist = $userIds->get(fake()->numberBetween(0, $userIds->count() - 1));
        $room->status = new RoomStatus(
            started: true,
            round: 0,
            time: Carbon::now()->addSeconds($roomSettings->timeLimit)->toDateTimeString('second'),
            term: 'test',
            guesses: 0,
        );
        $room->save();
        RoundHandler::dispatch($room, $room->status->round)->delay(now()->addSeconds($roomSettings->timeLimit));
        broadcast(new StartGame($room));

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('room.game', $room)]);
        }

        return response()->redirectToRoute('room.game', $room);
    }

    public function stop(Request $request, Room $room)
    {
        try {
            $this->gameService->stop($request->user(), $room);
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return response()->json(['message' => 'game stopped']);
    }

    public function finish(Room $room): void
    {
        $this->gameService->finish($room);
    }
}
