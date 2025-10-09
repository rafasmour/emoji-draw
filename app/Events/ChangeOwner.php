<?php

namespace App\Events;

use App\Models\Room;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChangeOwner
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public string $new_owner_id;
    public string $new_owner_name;
    public string $old_owner_id;
    public string $old_owner_name;
    public function __construct(
        private Room $room,
        User $new_owner,
        User $old_owner,
    )
    {
        $this->new_owner_id = $new_owner->getKey();
        $this->new_owner_name = $new_owner->name;
        $this->old_owner_id = $old_owner->getKey();
        $this->old_owner_name = $old_owner->name;
        $this->message = "{$old_owner->name} (owner) has changed to {$new_owner->name}!";

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("room.{$this->room->id}"),
        ];
    }
}
