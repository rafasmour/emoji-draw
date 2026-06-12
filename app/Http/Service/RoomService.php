<?php

namespace App\Http\Service;

use App\Contracts\RoomServiceInterface;
use App\DataObjects\RoomStatus;
use App\Events\RoomDestroyed;
use App\Models\Room;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as PaginatorInstance;
use Illuminate\Pagination\Paginator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoomService implements RoomServiceInterface
{
    public function findRoomWithUser(string $userId): ?Room
    {
        return Room::where('users.id', $userId)->first();
    }

    public function userInRoom(string $userId, Room $room): bool
    {
        return $room->users->contains('id', $userId);
    }

    public function create(User $owner, string $name): Room
    {
        $room = Room::create([
            'name' => $name,
            'owner' => $owner->id,
            'users' => [
                [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'score' => 0,
                    'guesses' => 0,
                    'correct_guesses' => 0,
                    'guessed' => false,
                ],
            ],
            'settings' => [
                'cap' => 10,
                'public' => true,
                'categories' => [],
                'difficulty' => 'easy',
                'language' => 'EN',
                'timeLimit' => 60,
                'rounds' => 5,
            ],
            'chat' => [],
            'canvas' => [],
            'kicked_users' => [],
            'status' => new RoomStatus(
                started: false,
                round: 0,
                time: '0',
                term: '',
                guesses: 0,
            ),
        ]);
        $room->save();

        return $room;
    }

    public function getPublicRooms(int $perPage = 10): LengthAwarePaginator
    {
        $currentPage = Paginator::resolveCurrentPage() ?: 1;
        $rooms = Room::query()
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn (Room $room): bool => (bool) $room->settings->public)
            ->values()
            ->map(fn (Room $room): array => [
                'id' => $room->getKey(),
                'name' => $room->name,
                'players' => count($room->users).'/'.$room->settings->cap,
            ]);

        return new PaginatorInstance(
            $rooms->forPage($currentPage, $perPage)->values(),
            $rooms->count(),
            $perPage,
            $currentPage,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ],
        );
    }

    public function destroy(User $user, Room $room): void
    {
        if (count($room->users) !== 0 && $user->id !== $room->owner) {
            throw new HttpException(403, 'Unauthorized.');
        }

        broadcast(new RoomDestroyed($room));
        $room->delete();
    }
}
