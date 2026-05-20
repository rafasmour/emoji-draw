<?php

namespace App\Http\Controllers\Room;

use App\Http\Contracts\RoomSettingsServiceInterface;
use App\Http\Controllers\Controller;
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

    public function updateSettings(Request $request, Room $room)
    {
        $validated = $request->validate([
            'cap' => ['integer', 'min:1', 'max:50'],
            'public' => ['boolean'],
            'timeLimit' => ['integer', '30|60|120'],
            'difficulty' => ['array', 'in_array:easy,medium,hard'],
            'categories' => ['array'],
            'rounds' => ['integer', 'min:1', 'max:10'],
        ]);

        try {
            $settings = $this->roomSettingsService->update($request->user(), $room, $validated);
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return response()->json(['message' => 'settings updated', 'settings' => $settings]);
    }
}
