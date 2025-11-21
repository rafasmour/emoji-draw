<?php

namespace App\Http\Controllers\Room;

use App\Events\ChatMessage;
use App\Events\ClearCanvas;
use App\Events\StartRound;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Term;
use App\RandomTerm;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RoundChangerController extends Controller
{
    use RandomTerm;

    public function changeRound(Room $room)
    {
        $roomSettings = $room->settings;
        $term = 'test';
        $roomStatus = $room->status;
        $roomStatus['term'] = $term;
        $roomStatus['round'] += 1;
        $roomStatus['guesses'] = 0;
        $roomStatus['time'] = Carbon::now()->addSeconds($roomSettings['timeLimit']);
        $room->status = $roomStatus;
        $canvas = $room->canvas ?? [];
        $canvas = [];
        $room->canvas = $canvas;
        $roomUsers = new Collection($room->users);
        $roomUsers = $roomUsers->map(fn($usr) => [
            ...$usr,
            'guessed' => false,
        ]);
        $room->users = $roomUsers->toArray();
        $chat = $room->chat ?? [];
        $message = [
            'user_id' => '1',
            'user' => 'System',
            'message' => 'Round Changed'
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
