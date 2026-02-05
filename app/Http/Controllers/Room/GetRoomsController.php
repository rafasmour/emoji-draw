<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Inertia\Inertia;

class GetRoomsController extends Controller
{
    public function __construct(public Room $room) {}

    public function index()
    {
        $rooms = Room::where('settings.public', true)->get();
        $rooms = $rooms->map(fn (Room $room) => [
            'id' => $room->getKey(),
            'name' => $room->name,
            'users' => "{$room->users->count()}/{$room->settings->cap}",
        ]);

        return Inertia::render('room/index', [
            'rooms' => $rooms,
        ]);
    }
}
