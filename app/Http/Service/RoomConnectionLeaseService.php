<?php

namespace App\Http\Service;

use App\Http\Contracts\RoomConnectionLeaseServiceInterface;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class RoomConnectionLeaseService implements RoomConnectionLeaseServiceInterface
{
    private const STORE = 'mongodb_room_leases';

    public function touch(Room $room, User $user, string $tabId, ?CarbonInterface $now = null): void
    {
        $now ??= now();

        $leases = $this->pruneLeases($this->readLeases($room, $user->getKey()), $now);
        $leases[$tabId] = $now->getTimestamp();

        $this->storeLeases($room, $user->getKey(), $leases, $now);
    }

    public function freshTabs(Room $room, string $userId, ?CarbonInterface $now = null): array
    {
        $now ??= now();

        $leases = $this->pruneLeases($this->readLeases($room, $userId), $now);

        if ($leases === []) {
            $this->clearUser($room, $userId);

            return [];
        }

        $this->storeLeases($room, $userId, $leases, $now);

        return array_keys($leases);
    }

    public function hasFreshTabs(Room $room, string $userId, ?CarbonInterface $now = null): bool
    {
        return $this->freshTabs($room, $userId, $now) !== [];
    }

    public function clearUser(Room $room, string $userId): void
    {
        Cache::store(self::STORE)->forget($this->key($room, $userId));
    }

    public function clearRoom(Room $room): void
    {
        foreach ($room->users as $roomUser) {
            $this->clearUser($room, $roomUser->id);
        }
    }

    /**
     * @return array<string, int>
     */
    private function readLeases(Room $room, string $userId): array
    {
        $leases = Cache::store(self::STORE)->get($this->key($room, $userId), []);

        if (! is_array($leases)) {
            return [];
        }

        return collect($leases)
            ->filter(fn (mixed $timestamp, mixed $tabId): bool => is_string($tabId) && (is_int($timestamp) || is_numeric($timestamp)))
            ->mapWithKeys(fn (mixed $timestamp, string $tabId): array => [$tabId => (int) $timestamp])
            ->all();
    }

    /**
     * @param  array<string, int>  $leases
     * @return array<string, int>
     */
    private function pruneLeases(array $leases, CarbonInterface $now): array
    {
        $cutoffTimestamp = $now->copy()->subSeconds(config('room.disconnect_grace_seconds'))->getTimestamp();

        return collect($leases)
            ->filter(fn (int $timestamp): bool => $timestamp >= $cutoffTimestamp)
            ->all();
    }

    /**
     * @param  array<string, int>  $leases
     */
    private function storeLeases(Room $room, string $userId, array $leases, CarbonInterface $now): void
    {
        Cache::store(self::STORE)->put(
            $this->key($room, $userId),
            $leases,
            max(
                config('room.disconnect_grace_seconds') * 2,
                config('room.disconnect_grace_seconds') + config('room.heartbeat_interval_seconds'),
                60,
            ),
        );
    }

    private function key(Room $room, string $userId): string
    {
        return "room-lease:{$room->getKey()}:{$userId}";
    }
}
