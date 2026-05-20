<?php

namespace App\Http\Contracts;

use App\DataObjects\RoomSettings;
use App\Models\Room;
use App\Models\User;

interface RoomSettingsServiceInterface
{
    public function update(User $user, Room $room, array $settings): RoomSettings;
}
