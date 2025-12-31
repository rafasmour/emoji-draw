<?php

namespace App\DataObjects;

readonly class CanvasElement
{
    public function __construct(
        public float $x,
        public float $y,
        public string $emoji,
        public int $size,
    ) {}
}
