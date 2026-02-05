<?php

namespace App\DataObjects;

readonly class CanvasElement
{
    public function __construct(
        public float $x = 0,
        public float $y = 0,
        public string $emoji = '',
        public int $size = 1,
    ) {}
}
