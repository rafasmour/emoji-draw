<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Http\Service\RoomService;
use Illuminate\Http\Request;

class CreateRoomController extends Controller
{
    public function __construct(
        private RoomService $roomService,
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:1', 'max:255', 'unique:rooms,name'],
        ]);

        $room = $this->roomService->create($request->user(), $validated['name']);

        return redirect()->route('room.lobby', $room);
    }
}
