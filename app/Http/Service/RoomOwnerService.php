<?php

namespace App\Http\Service;

use App\Events\ChangeOwner;
use App\Http\Contracts\ChatServiceInterface;
use App\Http\Contracts\RoomOwnerServiceInterface;
use App\Models\Room;
use App\Models\User;
use App\UserInRoom;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoomOwnerService implements RoomOwnerServiceInterface
{
    use UserInRoom;

    public function __construct(
        private ChatServiceInterface $chatService,
    ) {}

    public function changeOwner(User $requester, Room $room, string $newOwnerId): void
    {
        if ($requester->getKey() !== $room->owner) {
            throw new HttpException(403, 'Unauthorized.');
        }

        if (! $this->userInRoom($newOwnerId, $room)) {
            throw new HttpException(403, 'User not in room.');
        }

        $room->owner = $newOwnerId;
        $newOwner = User::find($newOwnerId);

        $message = [
            'user_id' => $requester->id,
            'user' => $requester->name,
            'message' => "Changed Owner to {$newOwner->name}",
        ];
        $chatMessages = $room->chat ?? [];
        $chatMessages[] = $message;
        $room->chat = $chatMessages;
        $room->save();
        $room->refresh();

        broadcast(new ChangeOwner($room, $newOwner));
        $this->chatService->broadcastMessage($room, $message);
    }

    public function assignRandomOwner(Room $room): void
    {
        $userIds = $room->users->pluck('id');
        $randomIndex = fake()->numberBetween(0, $userIds->count() - 1);
        $room->owner = $userIds->get($randomIndex);
        $newOwner = User::find($room->owner);

        $message = [
            'user_id' => $newOwner->getKey(),
            'user' => $newOwner->name,
            'message' => "Owner left the new owner is {$newOwner->name}",
        ];
        $chatMessages = $room->chat ?? [];
        $chatMessages[] = $message;
        $room->chat = $chatMessages;
        $room->save();
        $room->refresh();

        broadcast(new ChangeOwner($room, $newOwner));
        $this->chatService->broadcastMessage($room, $message);
    }
}
