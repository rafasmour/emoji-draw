<?php

namespace App\Listeners;

use App\Events\OwnerLeave;
use App\Http\Controllers\Room\RoomOwnerController;

class OwnerLeft
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OwnerLeave $event): void
    {
        $room = $event->getRoom();
        RoomOwnerController::randomOwner($room);
    }


}
