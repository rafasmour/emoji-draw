<?php

namespace App\Http\Contracts;

use App\Models\Room;
use App\Models\User;

interface RoomEntranceServiceInterface
{
    public function join(User $user, Room $room): void;

    public function leave(User $user, Room $room): void;

    public function kick(User $owner, Room $room, string $targetUserId): void;
}
