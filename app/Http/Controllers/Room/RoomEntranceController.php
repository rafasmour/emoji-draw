<?php

namespace App\Http\Controllers\Room;

use App\Http\Contracts\RoomEntranceServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoomEntranceController extends Controller
{
    public function __construct(
        private RoomEntranceServiceInterface $roomEntranceService,
    ) {}

    public function join(Request $request): mixed
    {
        $validated = $request->validate([
            'room_id' => ['required', 'string'],
        ]);

        $room = Room::where('id', (string) $validated['room_id'])->first();

        try {
            $this->roomEntranceService->join($request->user(), $room);
        } catch (HttpException $e) {
            if ($e->getStatusCode() === 422) {
                return Inertia::render('room/full', []);
            }

            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('room.lobby', $room)]);
        }

        return response()->redirectToRoute('room.lobby', $room);
    }

    public function leave(Request $request, Room $room): mixed
    {
        try {
            $this->roomEntranceService->leave($request->user(), $room);

            return redirect()->route('room.rooms');
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function kick(Request $request, Room $room): mixed
    {
        $validated = $request->validate([
            'user_id' => ['required', 'string'],
        ]);

        try {
            $this->roomEntranceService->kick($request->user(), $room, $validated['user_id']);
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return response()->json(['message' => 'player kicked']);
    }
}
