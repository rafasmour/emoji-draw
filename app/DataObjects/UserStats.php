<?php

namespace App\DataObjects;

class UserStats
{
    public float $accuracy;

    public function __construct(
        public string $guesses,
        public string $correct_guesses,
    ) {
        $this->accuracy = ($this->correct_guesses / $this->guesses) * 100;
    }
}
