<?php

namespace App\DataObjects;

readonly class RoomStatus
{
    public function __construct(
        public bool $started,
        public int $round,
        public string $time,
        public string $term,
        public int $guesses,
    ) {}
}
