<?php

namespace App;

use App\Models\Room;

trait UserInRoom
{
    public function userInRoom(string $userId, Room $room): bool
    {
        $userIds = array_map(fn($usr) => $usr['id'], $room->users);
        return in_array($userId, $userIds);
    }
}
