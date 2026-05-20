<?php

namespace App\Contracts;

use App\Models\Room;
use App\Models\User;

interface RoomServiceInterface
{
    public function findRoomWithUser(string $userId): ?Room;

    public function userInRoom(string $userId, Room $room): bool;

    public function addUser(Room $room, User $user): void;

    public function removeUser(Room $room, User $user): string;

    public function kickUser(Room $room, string $targetUserId, User $owner): void;
}
