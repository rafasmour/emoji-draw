<?php

namespace App\Events;

use App\Models\Room;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ArtistTermAssigned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $event = 'ArtistTermAssigned';

    public string $term;

    public int $round;

    public function __construct(
        private Room $room,
    ) {
        $this->term = $room->status->term;
        $this->round = $room->status->round;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("room.{$this->room->getKey()}.user.{$this->room->artist}");
    }
}
