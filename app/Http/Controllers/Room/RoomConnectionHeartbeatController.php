<?php

namespace App\Http\Controllers\Room;

use App\Http\Contracts\RoomConnectionLeaseServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Room\RoomConnectionHeartbeatRequest;
use App\Models\Room;
use Illuminate\Http\Response;

class RoomConnectionHeartbeatController extends Controller
{
    public function __construct(
        private RoomConnectionLeaseServiceInterface $roomConnectionLeaseService,
    ) {}

    public function store(RoomConnectionHeartbeatRequest $request, Room $room): Response
    {
        $this->roomConnectionLeaseService->touch(
            $room,
            $request->user(),
            $request->string('tab_id')->toString(),
        );

        return response()->noContent();
    }
}
