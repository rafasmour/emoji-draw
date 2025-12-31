<?php

namespace App\DataObjects;

readonly class RoomUser
{
    public function __construct(
        public string $id,
        public string $name,
        public int $score,
        public int $guesses,
        public bool $guessed,
        public ?string $room_token = null,
    ) {}
}
