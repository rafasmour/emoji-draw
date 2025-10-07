<?php

namespace App\Http\Controllers\Room;

use App\Events\RoomPublicChanged;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomSettingsController extends Controller
{
    public function settings(Request $request, Room $room)
    {

        return $room->settings;
    }

    public function updateSettings(Request $request, Room $room)
    {
        if($request->user()->getKey() === $room->owner)
        {
            return response()->json(['message' => 'unauthorized'], 403);
        }
        $validated = $request->validate([
            'cap' => ['integer', 'min:1', 'max:50'],
            'public' => ['boolean'],
            'timeLimit' => ['integer', 'min:50', 'max:200'],
            'difficulty' => ['array', 'in_array:easy,medium,hard'],
            'categories' => ['array'],
        ]);
        $currentSettings = $room->settings;
        $roomPublicChanged = $validated['public'] !== $currentSettings['public'];
        $room->settings = [
            ...$currentSettings,
            ...$validated,
        ];
        $room->save();
        $room->refresh();
        if($roomPublicChanged) {
            broadcast(new RoomPublicChanged($room->settings['public'], $room))->toOthers();
        }
        return response()->json(['message' => 'settings updated', 'settings' => $room->settings]);
    }
}
