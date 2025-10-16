<?php

namespace App\Listeners;

use App\Events\StartGame;
use App\Events\StopGame;

class GameInitializer
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
    public function handle(StartGame|StopGame $event): void
    {
        if ($event instanceof StartGame) {
            $this->start($event);
        }
        if ($event instanceof StopGame) {
            $this->stop($event);
        }
    }

    private function start(StartGame $event)
    {

    }

    private function stop(StopGame $event)
    {

    }
}
