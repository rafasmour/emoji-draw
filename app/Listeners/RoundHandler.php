<?php

namespace App\Listeners;

use App\Events\StartRound;
use App\Events\StopRound;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RoundHandler
{
    /**
     *
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(StartRound|StopRound $event): void
    {
        if($event instanceof StartRound) {
            $this->start($event);
        }
        if($event instanceof StopRound) {
            $this->stop($event);
        }
    }

    public function start(StartRound $event): void
    {

    }
    public function stop(StopRound $event): void
    {

    }
}
