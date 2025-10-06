<?php

namespace App\Listeners;

use App\Events\ChangeRoomSettings;
use App\Models\Room;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RoomSettings
{
    /**
     * Create the event listener.
     */
    public function __construct(
        public User $user,
        public Room $room,
    )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function __invoke(ChangeRoomSettings $event): void
    {

    }
}
