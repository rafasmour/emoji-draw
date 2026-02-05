<?php

namespace App\DataObjects;

readonly class RoomUser
{
    public function __construct(
        public string $id = '',
        public string $name = '',
        public int $score = 0,
        public int $guesses = 0,
        public bool $guessed = false,
        public int $correct_guesses = 0,
        public ?string $room_token = null,
    ) {}
}
