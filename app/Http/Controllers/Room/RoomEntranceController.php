<?php

namespace App\Http\Controllers\Room;

use App\Events\ChatMessage;
use App\Events\Join;
use App\Events\Leave;
use App\Events\OwnerLeave;
use App\Events\PlayerKicked;
use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureUserInRoom;
use App\Models\Room;
use App\Models\User;
use App\UserInRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class RoomEntranceController extends Controller
{
    use UserInRoom;

    public function join(Request $request)
    {
        $validated = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
        ]);
        $room = Room::find($validated['room_id']);
        if (count($room->users) === $room->settings['cap']) {
            return Inertia::render('room/full', []);
        }
        $roomUsers = $room->users;
        $roomUsers[] = [
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'score' => 0,
            'guesses' => 0,
            'correct_guesses' => 0,
            'artist' => false,
        ];
        $room->users = $roomUsers;
        $roomChat = $room->chat ?? [];
        $message = [
            'user_id' => $request->user()->id,
            'user' => $request->user()->name,
            'message' => 'Joined the Room!',
        ];
        $roomChat[] = $message;
        $room->chat = $roomChat;
        $room->save();
        $room->refresh();
        broadcast(new Join($request->user(), $room))->toOthers();
        broadcast(new ChatMessage($room, $message));
        return response()->redirectToRoute('room.lobby', $room);
    }

    public function leave(Request $request, Room $room)
    {
        $user = $request->user();
        $newUsers = Collection::make($room->users ?? [])
            ->filter(fn($roomUser) => $roomUser['id'] !== $user->id)
            ->values()
            ->toArray();
        if (count($newUsers) === count($room->users)) {
            return \response()->json(['message' => 'user not found'], 404);
        }
        if (count($newUsers) === 0) {
            $room->delete();
            return response()->redirectToRoute('room.rooms');
        }
        $room->users = $newUsers;
        $roomChat = $room->chat ?? [];
        $message = [
            'user_id' => $request->user()->id,
            'user' => $request->user()->name,
            'message' => 'Left the Room!',
        ];
        $roomChat[] = $message;
        $room->chat = $roomChat;
        $room->save();
        $room->refresh();
        if ($user->getKey() === $room->owner) {
            event(new OwnerLeave($user, $room, $message));
        }
        broadcast(new Leave($user, $room))->toOthers();
        broadcast(new ChatMessage($room, $message));

        return response()->redirectToRoute('room.rooms');
    }

    public function kick(Request $request, Room $room)
    {
        $user = $request->user();
        if($user->getKey() !== $room->owner) {
            return response()->json(['message' => 'unauthorized'], 403);
        }
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);
        if($user->getKey() === $validated['user_id'] || !$this->userInRoom($validated['user_id'], $room)) {
            return response()->json(['message' => "can't kick user", 403]);
        }
        $newUsers = Collection::make($room->users ?? [])
            ->filter(fn($roomUser) => $roomUser['id'] === $user->id)
            ->values()
            ->toArray();
        $playerKicked = User::find($validated['user_id']);
        if (count($newUsers) === count($room->users)) {
            return \response()->json(['message' => 'user not found'], 404);
        }
        $room->users = $newUsers;
        $roomChat = $room->chat ?? [];
        $message = [
            'user_id' => $request->user()->id,
            'user' => $request->user()->name,
            'message' => "{$user->name} kicked {$playerKicked->name}!",
        ];
        $roomChat[] = $message;
        $room->chat = $roomChat;
        $room->save();
        $room->refresh();
        broadcast(new PlayerKicked($playerKicked, $room));
        broadcast(new ChatMessage($room, $message));
    }
}
