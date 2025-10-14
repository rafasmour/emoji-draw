<?php

namespace App\Http\Controllers\Room;

use App\Events\StartRound;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\RandomTerm;

class RoundChangerController extends Controller
{
    use RandomTerm;
    public function changeRound(Room $room)
    {
        $term = $this->randomTerm();
        $room->status['term'] = '$term';
        $room->status['round']++;
        $room->status['guesses'] = 0;
        $room->save();
        broadcast(new StartRound($room));
    }
}
