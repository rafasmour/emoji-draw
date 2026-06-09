<?php

namespace App\Http\Service;

use App\Contracts\RoomServiceInterface;
use App\Contracts\UserServiceInterface;
use App\Models\Room;
use App\Models\User;

class UserService implements UserServiceInterface
{
    public function __construct(
        private RoomServiceInterface $roomService,
    ) {}

    public function findCurrentRoom(User $user): ?Room
    {
        return $this->roomService->findRoomWithUser($user->id);
    }
}
