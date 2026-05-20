<?php

namespace App\Http\Contracts;

use App\Models\Room;
use App\Models\User;

interface RoomOwnerServiceInterface
{
    public function changeOwner(User $requester, Room $room, string $newOwnerId): void;

    public function assignRandomOwner(Room $room): void;
}
