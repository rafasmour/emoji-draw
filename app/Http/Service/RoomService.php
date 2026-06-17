<?php

namespace App\Http\Service;

use App\Contracts\RoomServiceInterface;
use App\DataObjects\RoomUser;
use App\Events\ChatMessage;
use App\Events\Join;
use App\Events\Leave;
use App\Events\OwnerLeave;
use App\Events\PlayerKicked;
use App\Models\Room;
use App\Models\User;

use App\DataObjects\RoomStatus;
use App\Events\RoomDestroyed;
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

    public function addUser(Room $room, User $user): void
    {
        $room->users = $room->users->push(new RoomUser(
            id: $user->id,
            name: $user->name,
            score: 0,
            guesses: 0,
            correct_guesses: 0,
            guessed: false,
        ));
        $message = [
            'user_id' => $user->id,
            'user' => $user->name,
            'message' => 'Joined the Room!',
        ];
        $roomChat = $room->chat ?? [];
        $roomChat[] = $message;
        $room->chat = $roomChat;
        $room->save();
        $room->refresh();
        broadcast(new Join($user, $room))->toOthers();
        broadcast(new ChatMessage($room, $message));
    }

    public function removeUser(Room $room, User $user): string
    {
        $newUsers = $room->users->filter(fn (RoomUser $u) => $u->id !== $user->id)->values();
        if ($newUsers->count() === $room->users->count()) {
            return route('room.rooms');
        }
        if ($newUsers->isEmpty()) {
            $room->delete();

            return route('room.rooms');
        }
        $message = [
            'user_id' => $user->id,
            'user' => $user->name,
            'message' => 'Left the Room!',
        ];
        $roomChat = $room->chat ?? [];
        $roomChat[] = $message;
        $room->chat = $roomChat;
        $room->users = $newUsers;
        $room->save();
        $room->refresh();
        if ($user->getKey() === $room->owner) {
            event(new OwnerLeave($user, $room));
        }
        broadcast(new Leave($user, $room))->toOthers();
        broadcast(new ChatMessage($room, $message));

        return route('room.rooms');
    }

    public function kickUser(Room $room, string $targetUserId, User $owner): void
    {
        $target = User::find($targetUserId);
        $newUsers = $room->users->filter(fn (RoomUser $u) => $u->id !== $targetUserId)->values();
        $message = [
            'user_id' => $owner->id,
            'user' => $owner->name,
            'message' => "{$owner->name} kicked {$target->name}!",
        ];
        $roomChat = $room->chat ?? [];
        $roomChat[] = $message;
        $room->chat = $roomChat;
        $room->users = $newUsers;
        $room->kicked_users = array_merge($room->kicked_users ?? [], [$targetUserId]);
        $room->save();
        $room->refresh();
        broadcast(new PlayerKicked($target, $room));
        broadcast(new ChatMessage($room, $message));
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

    public function getPublicRooms(): array
    {
        return Room::all()->where('settings.public', true)->map(function (Room $room) {
            return [
                'id' => $room->getKey(),
                'name' => $room->name,
                'users' => count($room->users).'/'.$room->settings->cap,
            ];
        })->values()->toArray();
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
