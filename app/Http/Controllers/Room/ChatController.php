<?php

namespace App\Http\Controllers\Room;

use App\Http\Contracts\ChatServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        private ChatServiceInterface $chatService,
    ) {}

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

        $this->chatService->sendMessage($request->user(), $room, $validated['message']);
    }
}
