<?php

namespace App\Http\Contracts;

use App\Models\Room;
use App\Models\User;

interface ChatServiceInterface
{
    /**
     * Purify, persist, and broadcast a user-typed chat message.
     */
    public function sendMessage(User $user, Room $room, string $message): void;

    /**
     * Broadcast a pre-built message array (caller owns the DB save).
     */
    public function broadcastMessage(Room $room, array $message): void;

    /**
     * Broadcast a CorrectGuess event (caller owns the DB save).
     */
    public function broadcastCorrectGuess(User $user, Room $room): void;
}
