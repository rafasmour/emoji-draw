<?php

namespace App\Http\Contracts;

use App\Models\Room;
use App\Models\User;
use Carbon\CarbonInterface;

interface RoomConnectionLeaseServiceInterface
{
    public function touch(Room $room, User $user, string $tabId, ?CarbonInterface $now = null): void;

    /**
     * @return array<int, string>
     */
    public function freshTabs(Room $room, string $userId, ?CarbonInterface $now = null): array;

    public function hasFreshTabs(Room $room, string $userId, ?CarbonInterface $now = null): bool;

    public function clearUser(Room $room, string $userId): void;

    public function clearRoom(Room $room): void;
}
