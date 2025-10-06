<?php

namespace App\Http\Controllers\Room;

use App\Events\Join;
use App\Events\Leave;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Response;

class JoinLeaveController extends Controller
{
    public function join(Request $request, Room $room)
    {
        if ($room->users->count() === $room->settings['cap']) {
            return \response()->json(['message' => 'Room is full'], 403);
        }
        $room->users[] = [
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'score' => 0,
            'guesses' => 0,
            'correct_guesses' => 0,
            'artist' => false,
        ];
        $room->save();
        broadcast(new Join($request->user(), $room))->toOthers();
        return \response()->json(['message' => 'Joined room']);
    }

    public function leave(Request $request, Room $room)
    {
        $user = $request->user();
        $newUsers = Collection::make($room->users ?? [])
        ->filter(fn($roomUser) => $roomUser['id'] !== $user->id)
        ->values()
        ->toArray();
        if(count($newUsers) === count($room->$newUsers)) {
            return \response()->json(['message' => 'user not found'], 404);
        }
        $room->users = $newUsers;
        $room->save();
        broadcast(new Leave($user, $room))->toOthers();
        return \response()->json(['message' => 'Left room']);
    }
}
