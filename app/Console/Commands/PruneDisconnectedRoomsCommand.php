<?php

namespace App\Console\Commands;

use App\Http\Contracts\RoomConnectionLeaseServiceInterface;
use App\Http\Contracts\RoomEntranceServiceInterface;
use App\Models\Room;
use App\Models\User;
use Illuminate\Console\Command;

class PruneDisconnectedRoomsCommand extends Command
{
    protected $signature = 'rooms:prune-disconnected';

    protected $description = 'Remove room users whose active room connection lease has expired.';

    public function __construct(
        private RoomConnectionLeaseServiceInterface $roomConnectionLeaseService,
        private RoomEntranceServiceInterface $roomEntranceService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $now = now();

        foreach (Room::query()->get() as $room) {
            foreach ($room->users as $roomUser) {
                $freshRoom = Room::query()->find($room->getKey());

                if ($freshRoom === null || ! $freshRoom->users->contains('id', $roomUser->id)) {
                    continue;
                }

                if ($this->roomConnectionLeaseService->hasFreshTabs($freshRoom, $roomUser->id, $now)) {
                    continue;
                }

                $user = User::query()->find($roomUser->id);

                if ($user === null) {
                    $this->roomConnectionLeaseService->clearUser($freshRoom, $roomUser->id);

                    continue;
                }

                $this->roomEntranceService->leave($user, $freshRoom);
            }
        }

        return self::SUCCESS;
    }
}
