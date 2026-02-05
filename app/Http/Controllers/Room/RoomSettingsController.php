<?php

namespace App\Http\Controllers\Room;

use App\DataObjects\ChatMessage as ChatMessageDTO;
use App\DataObjects\RoomSettings;
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
        if ($request->user()->getKey() === $room->owner) {
            return response()->json(['message' => 'unauthorized'], 403);
        }

        $validated = $request->validate([
            'cap' => ['integer', 'min:1', 'max:50'],
            'public' => ['boolean'],
            'timeLimit' => ['integer', '30|60|120'],
            'difficulty' => ['array', 'in_array:easy,medium,hard'],
            'categories' => ['array'],
            'rounds' => ['integer', 'min:1', 'max:10'],
        ]);

        $currentSettings = $room->settings;
        $roomPublicChanged = isset($validated['public']) && $validated['public'] !== $currentSettings->public;

        $room->settings = new RoomSettings(
            difficulty: $validated['difficulty'] ?? $currentSettings->difficulty,
            public: $validated['public'] ?? $currentSettings->public,
            cap: $validated['cap'] ?? $currentSettings->cap,
            rounds: $validated['rounds'] ?? $currentSettings->rounds,
            categories: $validated['categories'] ?? $currentSettings->categories,
            language: $currentSettings->language,
            timeLimit: $validated['timeLimit'] ?? $currentSettings->timeLimit,
        );

        $message = new ChatMessageDTO(
            user_id: $request->user()->id,
            user_name: $request->user()->name,
            message: 'updated settings',
        );

        $room->chat = $room->chat->push($message);
        $room->save();
        $room->refresh();

        if ($roomPublicChanged) {
            broadcast(new RoomPublicChanged($room->settings->public, $room));
        }

        return response()->json(['message' => 'settings updated', 'settings' => $room->settings]);
    }
}
