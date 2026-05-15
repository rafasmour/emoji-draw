<?php

namespace App\Http\Controllers\Room;

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
        $chat = $room->chat ?? [];
        $message = [
            'user_id' => '1',
            'user' => 'System',
            'message' => 'Round Changed',
        ];
        $chat[] = $message;
        $room->chat = $chat;
        $room->save();
        $room->refresh();
        broadcast(new StartRound($room));
        broadcast(new ChatMessage($room, $message));
        broadcast(new ClearCanvas($room));
    }
}
