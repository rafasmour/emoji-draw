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
        return $room->chat;
    }

    public function sendMessage(Request $request, Room $room)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        if($room->started) {
            return redirect()->route('room.guess', [$room]);
        }
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        $message = $purifier->purify($validated['message']);
        $roomChat = $room->chat ?? [];
        $user = $request->user();
        $message = [
            'user_id' => $user->getKey(),
            'user' => $user->name,
            'message' => $message,
        ];
        $roomChat[] = $message;
        $room->chat = $roomChat;
        $room->save();
        $room->refresh();
        broadcast(new ChatMessage($room, $message));
    }
}
