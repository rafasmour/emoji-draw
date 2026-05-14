<?php

namespace App;

use App\Models\Room;

trait UserInRoom
{
    public function userInRoom(string $userId, Room $room): bool
    {
        return $room->users->contains('id', $userId);
    }
}
