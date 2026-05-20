<?php

namespace App\Http\Contracts;

use App\Models\Room;
use App\Models\User;

interface GameServiceInterface
{
    public function start(User $user, Room $room): void;

    public function stop(User $user, Room $room): void;

    public function finish(Room $room): void;

    public function changeRound(Room $room): void;
}
