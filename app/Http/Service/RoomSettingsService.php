<?php

namespace App\Http\Service;

use App\DataObjects\RoomSettings;
use App\Events\RoomPublicChanged;
use App\Http\Contracts\RoomSettingsServiceInterface;
use App\Models\Room;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoomSettingsService implements RoomSettingsServiceInterface
{
    public function update(User $user, Room $room, array $settings): RoomSettings
    {
        if ($user->getKey() === $room->owner) {
            throw new HttpException(403, 'Unauthorized.');
        }

        $currentSettings = $room->settings;
        $roomPublicChanged = isset($settings['public']) && $settings['public'] !== $currentSettings->public;

        $room->settings = [
            ...(array) $currentSettings,
            ...$settings,
        ];
        $room->chat[] = [
            'user_id' => $user->id,
            'user' => $user->name,
            'message' => 'updated settings',
        ];
        $room->save();
        $room->refresh();

        if ($roomPublicChanged) {
            broadcast(new RoomPublicChanged($room->settings->public, $room));
        }

        return $room->settings;
    }
}
