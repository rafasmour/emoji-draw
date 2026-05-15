<?php

namespace App\Jobs;

use App\Http\Controllers\Room\GameStateController;
use App\Http\Controllers\Room\RoundChangerController;
use App\Models\Room;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

class RoundHandler implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private Room $room,
        private int $forRound = -1,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->room->refresh();
        $currentRound = $this->room->status->round;
        $roomSettings = $this->room->settings;

        if ($this->forRound >= 0 && $currentRound !== $this->forRound) {
            $this->delete();

            return;
        }

        if ($currentRound === $roomSettings->rounds) {
            $gameInitializer = new GameStateController;
            $gameInitializer->finish($this->room);
            $this->delete();
        } else {
            $roundChanger = new RoundChangerController;
            $roundChanger->change($this->room);
            RoundHandler::dispatch($this->room, $this->room->status['round'])->delay(now()->addSeconds($roomSettings->timeLimit));
        }
    }
}
