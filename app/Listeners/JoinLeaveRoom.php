<?php

namespace App\Listeners;

use App\Events\Join;
use App\Events\Leave;
use App\Models\Room;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class JoinLeaveRoom
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
    public function handle(Join|Leave $event): void
    {
        if($event instanceof Join) {
            $this->join($event);
        }
        if($event instanceof Leave) {
            $this->leave($event);
        }
    }

    public function join(Join $event): void
    {

    }

    public function leave(Leave $event): void
    {

    }
}
