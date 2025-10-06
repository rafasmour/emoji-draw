<?php

namespace App\Http\Controllers\Room;

use App\Events\ChatMessage;
use App\Http\Controllers\Controller;
use App\Models\Room;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Http\Request;
use Stevebauman\Purify\Purify;

class ChatController extends Controller
{

    public function getMessages(Request $request, Room $room)
    {
        $user = $request->user();
        $roomUserIds = $room->users->pluck('id');
        if(!array_search($user->id, $roomUserIds)) {
            return response()->json(['message' => 'unauthorized'], 403);
        }
        return $room->chat;
    }

    public function sendMessage(Request $request, Room $room)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:255', ''],
        ]);
        $user = $request->user();
        $roomUserIds = $room->users->pluck('id');
        if(!array_search($user->id, $roomUserIds)) {
            return response()->json(['message' => 'unauthorized'], 403);
        }
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        $message = $purifier->purify($validated['message']);

        $room->chat->push([
            'user' => $user->name,
            'message' => $message,
        ]);
        $room->save();
        broadcast(new ChatMessage($room, $user, $message))->toOthers();
        return response()->json(['message' => 'Message sent']);
    }
}
