<?php

namespace App\DataObjects;

readonly class ChatMessage
{
    public function __construct(
        public string $user_id,
        public string $user,
        public string $message,
    ) {}
}
