<?php

namespace App\Http\Contracts;

use App\Models\Room;
use App\Models\User;

interface ChatServiceInterface
{
    public function sendMessage(User $user, Room $room, string $message): void;
}
