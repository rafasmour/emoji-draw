<?php

namespace App\Http\Controllers\Room;

use App\Contracts\RoomServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GetRoomsController extends Controller
{
    public function __construct(
        private RoomServiceInterface $roomService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('room/index', [
            'rooms' => $this->roomService->getPublicRooms(10),
        ]);
    }
}
