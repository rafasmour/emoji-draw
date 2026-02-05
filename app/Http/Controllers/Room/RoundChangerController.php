<?php

namespace App\Http\Controllers\Room;

use App\DataObjects\ChatMessage as ChatMessageDTO;
use App\DataObjects\RoomStatus;
use App\DataObjects\RoomUser;
use App\Events\ChatMessage;
use App\Events\ClearCanvas;
use App\Events\StartRound;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\RandomTerm;
use Carbon\Carbon;

class RoundChangerController extends Controller
{
    use RandomTerm;

    public function change(Room $room): void
    {
        $term = 'test';

        $room->status = new RoomStatus(
            round: $room->status->round + 1,
            time: Carbon::now()->addSeconds($room->settings->timeLimit)->toDateTimeString('second'),
            term: $term,
            started: $room->status->started,
        );

        $room->canvas = collect([]);

        $previousArtist = $room->artist;
        $userIds = $room->users
            ->filter(fn (RoomUser $u) => $u->id !== $previousArtist)
            ->pluck('id')
            ->filter()
            ->toArray();

        $room->artist = fake()->randomElement($userIds);

        $room->users = $room->users->map(fn (RoomUser $usr) => new RoomUser(
            id: $usr->id,
            name: $usr->name,
            score: $usr->score,
            guesses: $usr->guesses,
            guessed: false,
            correct_guesses: $usr->correct_guesses,
            room_token: $usr->room_token,
        ));

        $message = new ChatMessageDTO(
            user_id: '1',
            user_name: 'System',
            message: 'Round Changed',
        );

        $room->chat = $room->chat->push($message);
        $room->save();
        $room->refresh();

        broadcast(new StartRound($room));
        broadcast(new ChatMessage($room, $message));
        broadcast(new ClearCanvas($room));
    }
}
