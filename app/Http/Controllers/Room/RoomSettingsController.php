<?php

namespace App\Http\Controllers\Room;

use App\Http\Contracts\RoomSettingsServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Room\UpdateRoomSettingsRequest;
use App\Models\Room;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoomSettingsController extends Controller
{
    public function __construct(
        private RoomSettingsServiceInterface $roomSettingsService,
    ) {}

    public function settings(Request $request, Room $room)
    {
        return $room->settings;
    }

    public function updateSettings(UpdateRoomSettingsRequest $request, Room $room)
    {
        try {
            $settings = $this->roomSettingsService->update($request->user(), $room, $request->validated());
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return response()->json(['message' => 'settings updated', 'settings' => $settings]);
    }
}
