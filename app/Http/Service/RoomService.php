<?php

namespace App\Http\Service;

use App\DataObjects\RoomStatus;
use App\Events\RoomDestroyed;
use App\Models\Room;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoomService
{
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

    public function destroy(User $user, Room $room): void
    {
        if (count($room->users) !== 0 && $user->id !== $room->owner) {
            throw new HttpException(403, 'Unauthorized.');
        }

        broadcast(new RoomDestroyed($room));
        $room->delete();
    }
}
