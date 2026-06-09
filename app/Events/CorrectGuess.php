<?php

namespace App\Events;

use App\DataObjects\RoomUser;
use App\Models\Room;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CorrectGuess implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $event = 'CorrectGuess';

    public string $user_id;

    public string $artist_id;

    public int $guesser_score;

    public int $artist_score;

    public bool $is_first_guess;

    /** @var array<int, array{id: string, score: int}> */
    public array $users;

    public function __construct(
        private User $user,
        private Room $room,
        int $guesserScore = 0,
        int $artistScore = 0,
        bool $isFirstGuess = false,
    ) {
        $this->user_id = (string) $user->getKey();
        $this->artist_id = $room->artist;
        $this->guesser_score = $guesserScore;
        $this->artist_score = $artistScore;
        $this->is_first_guess = $isFirstGuess;
        $this->users = $room->users
            ->map(fn (RoomUser $u) => ['id' => $u->id, 'score' => $u->score])
            ->values()
            ->all();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("room.{$this->room->getKey()}"),
        ];
    }
}
