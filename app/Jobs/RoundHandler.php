<?php

namespace App\Jobs;

use App\Events\GameOver;
use App\Events\StartRound;
use App\Http\Controllers\Room\GameStateController;
use App\Http\Controllers\Room\RoundChangerController;
use App\Models\Room;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RoundHandler implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private Room $room,
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->room->refresh();
        $currentRound = $this->room->status['round'];
        if($currentRound === $this->room->settings['rounds'])
        {
            $gameInitializer = new GameStateController();
            $gameInitializer->finish($this->room);
            $this->delete();
        }
        else
        {
            $roundChanger = new RoundChangerController();
            $roundChanger->changeRound($this->room);
        }
    }
}
