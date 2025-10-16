<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GetRoomsController extends Controller
{
    public function __construct(public Room $room)
    {
    }

    public function index(Request $request)
    {
        $rooms = $this->room->all()->where('settings.public', true);
        $rooms = $rooms->map(function (Room $room) {
            $userCount = count($room->users);
            $cap = $room->settings['cap'];
            return [
                'id' => $room->getKey(),
                'name' => $room->name,
                'users' => "{$userCount}/{$cap}",
            ];
        })->toArray();
        return Inertia::render('room/index', [
            'rooms' => $rooms,
        ]);
    }
}
