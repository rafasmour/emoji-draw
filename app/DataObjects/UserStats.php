<?php

namespace App\DataObjects;

class UserStats
{
    public float $accuracy;

    public function __construct(
        public string $guesses = '0',
        public string $correct_guesses = '1',
    ) {
        $this->accuracy = ((int) $this->correct_guesses / (int) ($this->guesses ?? 1)) * 100;
    }
}
