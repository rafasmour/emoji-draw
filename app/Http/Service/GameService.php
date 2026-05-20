<?php

namespace App\Http\Service;

use App\DataObjects\RoomStatus;
use App\DataObjects\RoomUser;
use App\Events\CanvasStroke;
use App\Events\ChatMessage;
use App\Events\ClearCanvas;
use App\Events\GameOver;
use App\Events\StartGame;
use App\Events\StartRound;
use App\Events\StopGame;
use App\Http\Contracts\GameServiceInterface;
use App\Jobs\HintHandler;
use App\Jobs\RoundHandler;
use App\Models\Room;
use App\Models\User;
use App\RandomTerm;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GameService implements GameServiceInterface
{
    use RandomTerm;

    public function start(User $user, Room $room): void
    {
        if (count($room->users) < 2) {
            throw new HttpException(403, 'Not enough users.');
        }

        if ($room->status->started) {
            throw new HttpException(403, 'Game already started.');
        }

        if ($user->id !== $room->owner) {
            throw new HttpException(403, 'Unauthorized.');
        }

        $roomSettings = $room->settings;
        $message = [
            'user_id' => $user->id,
            'user' => $user->name,
            'message' => 'started game',
        ];
        $roomChat = $room->chat ?? [];
        $roomChat[] = $message;
        $room->chat = $roomChat;

        $userIds = $room->users->pluck('id')->unique()->values();
        $room->artist = $userIds->get(fake()->numberBetween(0, $userIds->count() - 1));
        $room->status = new RoomStatus(
            started: true,
            round: 0,
            time: Carbon::now()->addSeconds($roomSettings->timeLimit)->toDateTimeString('second'),
            term: 'test',
            guesses: 0,
        );
        $room->save();

        RoundHandler::dispatch($room, $room->status->round)->delay(now()->addSeconds($roomSettings->timeLimit));
        broadcast(new StartGame($room));
    }

    public function stop(User $user, Room $room): void
    {
        if ($user->id !== $room->owner) {
            throw new HttpException(403, 'Unauthorized.');
        }

        $message = [
            'user_id' => $user->id,
            'user' => $user->name,
            'message' => 'stopped game',
        ];
        $roomChat = $room->chat ?? [];
        $roomChat[] = $message;
        $room->chat = $roomChat;
        $room->canvas = [];
        $room->status = new RoomStatus(
            started: false,
            round: 0,
            time: '0',
            term: '',
            guesses: 0,
        );
        $room->users = $room->users->map(fn (RoomUser $u) => new RoomUser(
            id: $u->id,
            name: $u->name,
            score: $u->score,
            guesses: 0,
            correct_guesses: 0,
            guessed: false,
            room_token: $u->room_token,
        ));
        $room->save();

        broadcast(new StopGame($room));
        broadcast(new ChatMessage($room, $message));
    }

    public function finish(Room $room): void
    {
        $room->users = $room->users->map(function (RoomUser $userStats) {
            $user = User::find($userStats->id);
            if ($user) {
                $user->guess_count = $userStats->guesses;
                if ($userStats->guesses > 0) {
                    $user->guess_accuracy = $userStats->correct_guesses / $userStats->guesses;
                }
                $user->save();
            }

            return new RoomUser(
                id: $userStats->id,
                name: $userStats->name,
                score: $userStats->score,
                guesses: 0,
                correct_guesses: 0,
                guessed: false,
                room_token: $userStats->room_token,
            );
        });

        $room->status = new RoomStatus(
            started: false,
            round: 0,
            time: '0',
            term: '',
            guesses: 0,
        );

        $message = [
            'user_id' => '1',
            'user' => 'System',
            'message' => 'Game Finished!',
        ];
        $chat = $room->chat ?? [];
        $chat[] = $message;
        $room->chat = $chat;
        $room->save();

        broadcast(new GameOver($room));
        broadcast(new ChatMessage($room, $message));
    }

    public function changeRound(Room $room): void
    {
        $roomSettings = $room->settings;
        $term = $this->randomTerm();
        $room->status = new RoomStatus(
            started: $room->status->started,
            round: $room->status->round + 1,
            time: Carbon::now()->addSeconds($roomSettings->timeLimit)->toDateTimeString('second'),
            term: $term,
            guesses: 0,
        );
        $room->canvas = [];

        $previousArtist = $room->artist;
        $userIds = $room->users
            ->filter(fn (RoomUser $u) => $u->id !== $previousArtist)
            ->pluck('id')
            ->values()
            ->all();
        $room->artist = fake()->randomElement($userIds);

        $room->users = $room->users->map(fn (RoomUser $usr) => new RoomUser(
            id: $usr->id,
            name: $usr->name,
            score: $usr->score,
            guesses: $usr->guesses,
            correct_guesses: $usr->correct_guesses,
            guessed: false,
            room_token: $usr->room_token,
        ));

        $message = [
            'user_id' => '1',
            'user' => 'System',
            'message' => 'Round Changed',
        ];
        $chat = $room->chat ?? [];
        $chat[] = $message;
        $room->chat = $chat;
        $room->save();
        $room->refresh();

        broadcast(new StartRound($room));
        broadcast(new ChatMessage($room, $message));
        broadcast(new ClearCanvas($room));

        HintHandler::dispatch($room, $room->status->round)
            ->delay(now()->addSeconds(HintHandler::HINT_INTERVAL_SECONDS));
    }
}
