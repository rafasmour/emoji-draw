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
