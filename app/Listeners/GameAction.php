<?php

namespace App\Listeners;

use App\Events\CanvasStroke;
use App\Events\CorrectGuess;
use App\Events\GuessTerm;
use App\Models\Room;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class GameAction
{
    /**
     * Create the event listener.
     */
    public function __construct(
    )
    {
    }

    /**
     * Handle the event.
     */
    public function handle(CanvasStroke|GuessTerm|CorrectGuess $event): void
    {
        if($event instanceof CanvasStroke) {
            $this->stroke($event);
        }
        if($event instanceof GuessTerm) {
            $this->guess($event);
        }
        if($event instanceof CorrectGuess) {
            $this->correct($event);
        }
    }

    public function stroke(CanvasStroke $event) {
    }

    public function guess(GuessTerm $event) {

    }

    public function correct(CorrectGuess $event) {

    }
}
