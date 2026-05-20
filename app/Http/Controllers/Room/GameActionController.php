<?php

namespace App\Http\Controllers\Room;

use App\Http\Contracts\GameActionServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Rules\EmojiOnly;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Validator;

class GameActionController extends Controller
{
    public function __construct(
        private GameActionServiceInterface $gameActionService,
    ) {}

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

        try {
            $this->gameActionService->handleStroke($request->user(), $room, $validated->validated());
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function guess(Request $request, Room $room)
    {
        $validated = $request->validate([
            'guess' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        try {
            $this->gameActionService->handleGuess($request->user(), $room, $validated['guess']);
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
    }
}
