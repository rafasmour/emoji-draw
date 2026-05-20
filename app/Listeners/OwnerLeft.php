<?php

namespace App\Listeners;

use App\Events\OwnerLeave;
use App\Http\Contracts\RoomOwnerServiceInterface;

class OwnerLeft
{
    public function __construct(
        private RoomOwnerServiceInterface $roomOwnerService,
    ) {}

    public function handle(OwnerLeave $event): void
    {
        $this->roomOwnerService->assignRandomOwner($event->getRoom());
    }
}
