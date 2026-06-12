<?php

namespace App\Http\Service;

use App\DataObjects\RoomStatus;
use App\DataObjects\RoomUser;
use App\Events\CanvasStroke;
use App\Events\CorrectGuess;
use App\Http\Contracts\ChatServiceInterface;
use App\Http\Contracts\GameActionServiceInterface;
use App\Jobs\RoundHandler;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GameActionService implements GameActionServiceInterface
{
    private const MAX_POINTS = 500;

    private const MIN_POINTS = 50;

    private const FIRST_GUESS_BONUS = 100;

    private const ARTIST_SCORE_RATIO = 0.5;

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

        $correct = $guess === $room->status->term;
        $guesserScore = 0;
        $artistScore = 0;
        $isFirstGuess = false;

        $userStats = new RoomUser(
            id: $userStats->id,
            name: $userStats->name,
            score: $userStats->score,
            guesses: $userStats->guesses + 1,
            correct_guesses: $userStats->correct_guesses,
            guessed: $userStats->guessed,
            room_token: $userStats->room_token,
        );

        if ($correct) {
            $roundEndsAt = Carbon::parse($room->status->time);
            $timeRemaining = $roundEndsAt->gt(now()) ? (int) now()->diffInSeconds($roundEndsAt) : 0;
            $timeRatio = $room->settings->timeLimit > 0 ? $timeRemaining / $room->settings->timeLimit : 0;
            $guesserScore = (int) floor($timeRatio * (self::MAX_POINTS - self::MIN_POINTS)) + self::MIN_POINTS;
            $isFirstGuess = $room->status->guesses === 0;

            if ($isFirstGuess) {
                $guesserScore += self::FIRST_GUESS_BONUS;
            }

            $artistScore = (int) floor($guesserScore * self::ARTIST_SCORE_RATIO);

            $userStats = new RoomUser(
                id: $userStats->id,
                name: $userStats->name,
                score: $userStats->score + $guesserScore,
                guesses: $userStats->guesses,
                correct_guesses: $userStats->correct_guesses + 1,
                guessed: true,
                room_token: $userStats->room_token,
            );

            $artistId = $room->artist;
            $room->users = $room->users->map(function (RoomUser $roomUser) use ($artistId, $artistScore, $user, $userStats) {
                if ($roomUser->id === $user->id) {
                    return $userStats;
                }

                if ($roomUser->id === $artistId) {
                    return new RoomUser(
                        id: $roomUser->id,
                        name: $roomUser->name,
                        score: $roomUser->score + $artistScore,
                        guesses: $roomUser->guesses,
                        correct_guesses: $roomUser->correct_guesses,
                        guessed: $roomUser->guessed,
                        room_token: $roomUser->room_token,
                    );
                }

                return $roomUser;
            });

            $room->status = new RoomStatus(
                started: $room->status->started,
                round: $room->status->round,
                time: $room->status->time,
                term: $room->status->term,
                guesses: $room->status->guesses + 1,
            );

            $message = [
                'user_id' => $user->id,
                'user' => $user->name,
                'message' => 'Guessed Correctly!',
            ];
            $chat = $room->chat ?? [];
            $chat[] = $message;
            $room->chat = $chat;
            $this->chatService->broadcastMessage($room, $message);
        } else {
            $room->users = $room->users->map(fn (RoomUser $roomUser) => $roomUser->id === $user->id ? $userStats : $roomUser);
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

        broadcast(new CorrectGuess($user, $room, $guesserScore, $artistScore, $isFirstGuess));

        if ($correct) {
            $nonArtistUsers = $room->users->filter(fn (RoomUser $u) => $u->id !== $room->artist);
            if ($nonArtistUsers->every(fn (RoomUser $u) => $u->guessed)) {
                RoundHandler::dispatch($room, $room->status->round);
            }
        }
    }
}
