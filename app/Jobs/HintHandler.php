<?php

namespace App\Jobs;

use App\Concerns\BuildsHint;
use App\Events\RevealHint;
use App\Models\Room;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;

class HintHandler implements ShouldQueue
{
    use BuildsHint, Queueable, SerializesModels;

    const HINT_INTERVAL_SECONDS = 10;

    public function __construct(
        private Room $room,
        private int $forRound,
        private int $hintsRevealed = 0,
    ) {}

    public function handle(): void
    {
        $this->room->refresh();

        if ($this->room->status->round !== $this->forRound) {
            $this->delete();

            return;
        }

        $term = $this->room->status->term;
        $nextRevealed = $this->hintsRevealed + 1;
        $hint = $this->buildHint($term, $nextRevealed);

        broadcast(new RevealHint($this->room, $hint));

        $letterCount = mb_strlen(str_replace(' ', '', $term));

        if ($nextRevealed < $letterCount) {
            HintHandler::dispatch($this->room, $this->forRound, $nextRevealed)
                ->delay(now()->addSeconds(self::HINT_INTERVAL_SECONDS));
        }
    }
}
