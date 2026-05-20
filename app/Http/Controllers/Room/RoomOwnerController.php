<?php

namespace App\Http\Controllers\Room;

use App\Http\Contracts\RoomOwnerServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoomOwnerController extends Controller
{
    public function __construct(
        private RoomOwnerServiceInterface $roomOwnerService,
    ) {}

    public function changeOwner(Request $request, Room $room)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'string', 'exists:users,id'],
        ]);

        try {
            $this->roomOwnerService->changeOwner($request->user(), $room, $validated['user_id']);
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
    }
}
