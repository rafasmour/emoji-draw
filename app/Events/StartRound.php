<?php

namespace App\Events;

use App\Concerns\BuildsHint;
use App\Models\Room;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StartRound implements ShouldBroadcastNow
{
    use BuildsHint, Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public string $event = 'StartRound';

    public string $term;

    public string $artist_id;

    public string $time;

    public string $initial_hint;

    public function __construct(
        private Room $room,
    ) {
        $this->term = $room->status->term;
        $this->artist_id = $room->artist;
        $this->time = now()->diffInSeconds($room->status->time);
        $this->initial_hint = $this->buildHint($room->status->term, 0);
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
