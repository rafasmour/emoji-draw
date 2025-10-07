<?php

namespace App\Http\Controllers\Room;

use App\Events\StartGame;
use App\Events\StopGame;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class GameTogglerController extends Controller
{
    public function getStatus(Request $request, Room $room)
    {
        return $room->started;
    }

    public function start(Request $request, Room $room)
    {
        if ($request->user()->id !== $room->owner) {
            return response()->json(['message' => 'unauthorized'], 403);
        }
        if ($room->started) {
            return response()->json(['message' => 'game already started'], 403);
        }
        $room->started = true;
        $room->chat[] = [
            'user_id' => $request->user()->id,
            'user_name' => $request->user()->name,
            'message' => 'started game',
        ];
        $room->save();
        broadcast(new StartGame($room));
        return response()->json(['message' => 'game started']);

    }

    public function stop(Request $request, Room $room)
    {
        if ($request->user()->id !== $room->owner) {
            return response()->json(['message' => 'unauthorized'], 403);
        }
        if (!$room->started) {
            return response()->json(['message' => "game hasn't started"], 403);
        }
        $room->started = true;
        $room->chat[] = [
            'user_id' => $request->user()->id,
            'user_name' => $request->user()->name,
            'message' => 'stopped game',
        ];
        $room->save();
        broadcast(new StopGame($room));
    }
}
