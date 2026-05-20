<?php

namespace App\Jobs;

use App\Http\Contracts\GameServiceInterface;
use App\Models\Room;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

class RoundHandler implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        private Room $room,
        private int $forRound = -1,
    ) {}

    public function handle(GameServiceInterface $gameService): void
    {
        $this->room->refresh();
        $currentRound = $this->room->status->round;
        $roomSettings = $this->room->settings;

        if ($this->forRound >= 0 && $currentRound !== $this->forRound) {
            $this->delete();

            return;
        }

        if ($currentRound === $roomSettings->rounds) {
            $gameService->finish($this->room);
            $this->delete();
        } else {
            $gameService->changeRound($this->room);
            RoundHandler::dispatch($this->room)->delay(now()->addSeconds($roomSettings->timeLimit));
        }
    }
}
