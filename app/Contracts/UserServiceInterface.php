<?php

namespace App\Contracts;

use App\Models\Room;
use App\Models\User;

interface UserServiceInterface
{
    public function findCurrentRoom(User $user): ?Room;
}
