<?php

namespace App\Http\Service;

use App\Events\ChatMessage;
use App\Http\Contracts\ChatServiceInterface;
use App\Models\Room;
use App\Models\User;
use HTMLPurifier;
use HTMLPurifier_Config;

class ChatService implements ChatServiceInterface
{
    public function sendMessage(User $user, Room $room, string $message): void
    {
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);
        $clean = $purifier->purify($message);

        $entry = [
            'user_id' => $user->getKey(),
            'user' => $user->name,
            'message' => $clean,
        ];
        $roomChat = $room->chat ?? [];
        $roomChat[] = $entry;
        $room->chat = $roomChat;
        $room->save();
        $room->refresh();

        broadcast(new ChatMessage($room, $entry));
    }
}
