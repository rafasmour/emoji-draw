<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Http\Service\RoomService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GetRoomsController extends Controller
{
    public function __construct(
        private RoomService $roomService,
    ) {}

    public function index(Request $request)
    {
        return Inertia::render('room/index', [
            'rooms' => $this->roomService->getPublicRooms(),
        ]);
    }
}
