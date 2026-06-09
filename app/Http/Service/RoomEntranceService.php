<?php

namespace App\Http\Service;

use App\DataObjects\RoomUser;
use App\Events\Join;
use App\Events\Leave;
use App\Events\OwnerLeave;
use App\Events\PlayerKicked;
use App\Events\RoomDestroyed;
use App\Http\Contracts\ChatServiceInterface;
use App\Http\Contracts\GameServiceInterface;
use App\Http\Contracts\RoomEntranceServiceInterface;
use App\Models\Room;
use App\Models\User;
use App\UserInRoom;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoomEntranceService implements RoomEntranceServiceInterface
{
    use UserInRoom;

    public function __construct(
        private ChatServiceInterface $chatService,
        private GameServiceInterface $gameService,
    ) {}

    public function join(User $user, Room $room): void
    {
        if (in_array($user->id, $room->kicked_users ?? [], true)) {
            throw new HttpException(403, "You can't enter this room. You were kicked by the owner and can't rejoin.");
        }

        if (count($room->users) === $room->settings->cap) {
            throw new HttpException(422, 'Room is full.');
        }

        $this->kickFromOtherRooms($user, $room);

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
        $this->chatService->broadcastMessage($room, $message);
    }

    private function kickFromOtherRooms(User $user, Room $room): void
    {
        $otherRooms = Room::where('users.id', $user->id)->where('_id', '!=', $room->id)->get();
        foreach ($otherRooms as $otherRoom) {
            $this->leave($user, $otherRoom);
        }
    }

    public function leave(User $user, Room $room): void
    {
        $newUsers = $room->users->filter(fn (RoomUser $roomUser) => $roomUser->id !== $user->id)->values();

        if (count($newUsers) === count($room->users)) {
            throw new HttpException(404, 'User not found in room.');
        }

        if (count($newUsers) <= 0) {
            if ($room->status->started) {
                $owner = User::find($room->owner);
                $this->gameService->stop($owner, $room);
            }

            $room->users = $newUsers;
            broadcast(new RoomDestroyed($room));
            $room->delete();

            return;
        }

        $room->users = $newUsers;

        if ($newUsers->count() === 1 && $room->status->started) {
            $owner = User::find($room->owner);
            $this->gameService->stop($owner, $room);
        }

        $message = [
            'user_id' => $user->id,
            'user' => $user->name,
            'message' => 'Left the Room!',
        ];
        $roomChat = $room->chat ?? [];
        $roomChat[] = $message;
        $room->chat = $roomChat;
        $room->save();
        $room->refresh();

        if ($user->getKey() === $room->owner) {
            event(new OwnerLeave($user, $room, $message));
        }

        broadcast(new Leave($user, $room))->toOthers();
        $this->chatService->broadcastMessage($room, $message);
    }

    public function kick(User $owner, Room $room, string $targetUserId): void
    {
        if ($owner->getKey() !== $room->owner) {
            throw new HttpException(403, 'Unauthorized.');
        }

        if ($owner->getKey() === $targetUserId || ! $this->userInRoom($targetUserId, $room)) {
            throw new HttpException(422, "Can't kick user.");
        }

        $newUsers = $room->users->filter(fn (RoomUser $roomUser) => $roomUser->id !== $targetUserId)->values();

        if (count($newUsers) === count($room->users)) {
            throw new HttpException(404, 'User not found in room.');
        }

        $playerKicked = User::find($targetUserId);
        $room->users = $newUsers;

        if ($newUsers->count() === 1 && $room->status->started) {
            $this->gameService->stop($owner, $room);
        }

        $room->kicked_users = array_merge($room->kicked_users ?? [], [$targetUserId]);
        $kickedMessage = "You were kicked by {$owner->name}. You can't rejoin this room.";

        $message = [
            'user_id' => $owner->id,
            'user' => $owner->name,
            'message' => "{$owner->name} kicked {$playerKicked->name}!",
        ];
        $roomChat = $room->chat ?? [];
        $roomChat[] = $message;
        $room->chat = $roomChat;
        $room->save();
        $room->refresh();

        broadcast(new PlayerKicked($playerKicked, $room, $kickedMessage));
        $this->chatService->broadcastMessage($room, $message);
    }
}
