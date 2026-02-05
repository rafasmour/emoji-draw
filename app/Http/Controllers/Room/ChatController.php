<?php

namespace App\Http\Controllers\Room;

use App\DataObjects\ChatMessage as ChatMessageDTO;
use App\Events\ChatMessage;
use App\Http\Controllers\Controller;
use App\Models\Room;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Http\Request;

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

        if ($room->status->started) {
            return redirect()->route('room.guess', $room);
        }

        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        $cleanMessage = $purifier->purify($validated['message']);

        $user = $request->user();
        $message = new ChatMessageDTO(
            user_id: $user->getKey(),
            user_name: $user->name,
            message: $cleanMessage,
        );

        $room->chat = $room->chat->push($message);
        $room->save();
        $room->refresh();

        broadcast(new ChatMessage($room, $message));
    }
}
