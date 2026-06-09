<?php

namespace App\Http\Service;

use App\DataObjects\RoomUser;
use App\Events\CanvasStroke;
use App\Http\Contracts\ChatServiceInterface;
use App\Http\Contracts\GameActionServiceInterface;
use App\Jobs\RoundHandler;
use App\Models\Room;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GameActionService implements GameActionServiceInterface
{
    public function __construct(
        private ChatServiceInterface $chatService,
    ) {}

    public function handleStroke(User $user, Room $room, array $data): void
    {
        if ($room->artist !== $user->id) {
            throw new HttpException(403, 'Not artist.');
        }

        $canvas = $room->canvas ?? [];
        $canvas[] = $data;
        $room->canvas = $canvas;
        $room->save();
        $room->refresh();

        broadcast(new CanvasStroke($room, $data))->toOthers();
    }

    public function handleGuess(User $user, Room $room, string $guess): void
    {
        if ($user->getKey() === $room->artist) {
            throw new HttpException(403, "Artist can't guess.");
        }

        $userStats = $room->users->firstWhere('id', $user->id);

        if ($userStats->guessed) {
            throw new HttpException(403, 'Already guessed.');
        }

        // Validate guess parameter
        if (! is_string($guess) || empty(trim($guess))) {
            throw new HttpException(400, 'Invalid guess parameter.');
        }

        $correct = $guess === $room->status->term;

        // Update user stats in the database directly
        $userStats->guesses += 1;
        $userStats->guessed = true;
        if ($correct) {
            $userStats->correct_guesses += 1;
            $message = [
                'user_id' => $user->id,
                'user' => $user->name,
                'message' => 'Guessed Correctly!',
            ];
            $chat = $room->chat ?? [];
            $chat[] = $message;
            $room->chat = $chat;
            $room->users = $room->users->map(fn (RoomUser $usr) => $usr->id === $user->id ? $userStats : $usr);
            $this->chatService->broadcastMessage($room, $message);
        } else {
            $message = [
                'user_id' => $user->id,
                'user' => $user->name,
                'message' => $guess,
            ];
            $chat = $room->chat ?? [];
            $chat[] = $message;
            $room->chat = $chat;
            $this->chatService->broadcastMessage($room, $message);
        }

        $room->save();
        $this->chatService->broadcastCorrectGuess($user, $room);

        if ($correct) {
            $nonArtistUsers = $room->users->filter(fn (RoomUser $u) => $u->id !== $room->artist);
            if ($nonArtistUsers->every(fn (RoomUser $u) => $u->guessed)) {
                RoundHandler::dispatch($room, $room->status->round);
            }
        }
    }
}
