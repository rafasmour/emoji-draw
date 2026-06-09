<?php

namespace App\Events;

use App\DataObjects\RoomUser;
use App\Models\Room;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameOver implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $event = 'GameOver';

    /** @var array<int, array{id: string, name: string, score: int}> */
    public array $users;

    public function __construct(
        private Room $room,
    ) {
        $this->users = $room->users
            ->sortByDesc('score')
            ->map(fn (RoomUser $u) => ['id' => $u->id, 'name' => $u->name, 'score' => $u->score])
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
