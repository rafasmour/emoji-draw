<?php

namespace App\DataObjects;

readonly class RoomSettings
{
    public function __construct(
        public string $difficulty = '',
        public bool $public = true,
        public int $cap = 0,
        public int $rounds = 0,
        public array $categories = [],
        public string $language = 'en',
        public int $timeLimit = 0,
    ) {}

}
