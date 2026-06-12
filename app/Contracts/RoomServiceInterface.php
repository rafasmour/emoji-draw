<?php

namespace App\Contracts;

use App\Models\Room;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RoomServiceInterface
{
    public function findRoomWithUser(string $userId): ?Room;

    public function userInRoom(string $userId, Room $room): bool;

    public function getPublicRooms(int $perPage = 10): LengthAwarePaginator;

    public function create(User $owner, string $name): Room;

    public function destroy(User $user, Room $room): void;
}
