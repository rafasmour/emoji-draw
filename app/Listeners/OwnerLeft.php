<?php

namespace App\Listeners;

use App\Events\OwnerLeave;
use App\Http\Controllers\Room\RoomOwnerController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
