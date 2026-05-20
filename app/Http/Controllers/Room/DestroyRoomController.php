<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Http\Service\RoomService;
use App\Models\Room;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DestroyRoomController extends Controller
{
    public function __construct(
        private RoomService $roomService,
    ) {}

    public function destroy(Request $request, Room $room)
    {
        try {
            $this->roomService->destroy($request->user(), $room);
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('room.rooms')]);
        }

        return response()->redirectToRoute('room.rooms');
    }
}
