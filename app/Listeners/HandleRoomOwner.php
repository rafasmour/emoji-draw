<?php

namespace App\Listeners;

use App\Events\ChangeOwner;
use App\Events\OwnerLeave;
use App\Models\Room;
use App\Models\User;

class HandleRoomOwner
{
    /**
     * Create the event listener.
     */
    public function __construct(
        public User $user,
        public Room $room,
    )
    {
    }

    /**
     * Handle the event.
     */
    public function handle(OwnerLeave|ChangeOwner $event): void
    {
    }

    public function changeOwner(ChangeOwner $event)
    {

    }

}
