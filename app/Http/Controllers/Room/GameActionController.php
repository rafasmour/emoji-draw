<?php

namespace App\Http\Controllers\Room;

use App\DataObjects\ChatMessage;
use App\DataObjects\RoomStatus;
use App\DataObjects\RoomUser;
use App\Events\CorrectGuess;
use App\Http\Contracts\GameActionServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Rules\EmojiOnly;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Validator;

class GameActionController extends Controller
{
    private const MAX_POINTS = 500;

    private const MIN_POINTS = 50;

    private const FIRST_GUESS_BONUS = 100;

    private const ARTIST_SCORE_RATIO = 0.5;

    public function __construct(
        private GameActionServiceInterface $gameActionService,
    ) {}

    public function canvas(Request $request, Room $room)
    {
        return $room->canvas;
    }

    public function stroke(Request $request, Room $room)
    {
        $validated = Validator::make($request->all(), [
            'x' => ['required', 'integer', 'min:0', 'max:10000'],
            'y' => ['required', 'integer', 'min:0', 'max:10000'],
            'emoji' => ['required', 'max:5', new EmojiOnly],
            'size' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        if ($validated->fails()) {
            return response()->json($validated->errors()->toArray(), 400);
        }

        try {
            $this->gameActionService->handleStroke($request->user(), $room, $validated->validated());
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function guess(Request $request, Room $room)
    {
        $validated = $request->validate([
            'guess' => ['required', 'string', 'min:1', 'max:255'],
        ]);
        if ($user->getKey() === $room->artist) {
            return response()->json(['message' => "Artist Can't guess"], 403);
        }
        $guess = $validated['guess'];
        $correct = $guess === $room->status->term;
        $userStats = $room->users->firstWhere('id', $user->id);
        if ($userStats->guessed) {
            return response()->json(['message' => 'already guessed'], 403);
        }
        $userStats = new RoomUser(
            id: $userStats->id,
            name: $userStats->name,
            score: $userStats->score,
            guesses: $userStats->guesses + 1,
            correct_guesses: $userStats->correct_guesses,
            guessed: $userStats->guessed,
            room_token: $userStats->room_token,
        );

        $guesserScore = 0;
        $artistScore = 0;
        $isFirstGuess = false;

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
            $room->users = $room->users->map(function (RoomUser $usr) use ($user, $userStats, $artistId, $artistScore) {
                if ($usr->id === $user->id) {
                    return $userStats;
                }
                if ($usr->id === $artistId) {
                    return new RoomUser(
                        id: $usr->id,
                        name: $usr->name,
                        score: $usr->score + $artistScore,
                        guesses: $usr->guesses,
                        correct_guesses: $usr->correct_guesses,
                        guessed: $usr->guessed,
                        room_token: $usr->room_token,
                    );
                }

                return $usr;
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
            broadcast(new ChatMessage($room, $message));
        } else {
            $message = [
                'user_id' => $user->id,
                'user' => $user->name,
                'message' => $validated['guess'],
            ];
            $chat = $room->chat ?? [];
            $chat[] = $message;
            $room->chat = $chat;
            broadcast(new ChatMessage($room, $message));
        }
        $room->save();
        broadcast(new CorrectGuess($user, $room, $guesserScore, $artistScore, $isFirstGuess));

        try {
            $this->gameActionService->handleGuess($request->user(), $room, $validated['guess']);
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
    }
}
