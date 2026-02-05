<?php

namespace App\DataObjects;

class RoomStatus
{
    public function __construct(
        public int $round = 0,
        public int $time = 0,
        public string $term = '',
        public bool $started = false,
    ) {}
}
