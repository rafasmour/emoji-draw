<?php

namespace App\Http\Contracts;

use App\Models\Room;
use App\Models\User;

interface GameActionServiceInterface
{
    public function handleStroke(User $user, Room $room, array $data): void;

    public function handleGuess(User $user, Room $room, string $guess): void;
}
