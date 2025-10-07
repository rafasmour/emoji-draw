<?php

namespace App\Http\Controllers\Room;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class GetRoomsController extends Controller
{
    public function __construct(public Room $room)
    {
    }

    public function index(Request $request)
    {
        return $this->room->all();
    }
}
