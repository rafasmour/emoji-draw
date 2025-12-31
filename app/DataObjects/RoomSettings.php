<?php

namespace App\DataObjects;

readonly class RoomSettings
{
    public function __construct(
        public string $difficulty,
        public bool $public,
        public int $cap,
        public int $rounds,
        public array $categories,
        public string $language,
        public int $timeLimit,
    ) {}
}
