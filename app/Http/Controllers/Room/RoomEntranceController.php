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

    public function join(Request $request)
    {
        $validated = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
        ]);
        $room = Room::find($validated['room_id']);

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

    public function leave(Request $request, Room $room)
    {
        try {
            $this->roomEntranceService->leave($request->user(), $room);
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('room.rooms')]);
        }

        return response()->redirectToRoute('room.rooms');
    }

    public function kick(Request $request, Room $room)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        try {
            $this->roomEntranceService->kick($request->user(), $room, $validated['user_id']);
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
    }
}
