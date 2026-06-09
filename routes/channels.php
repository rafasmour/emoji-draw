<?php

use App\Contracts\RoomServiceInterface;
use App\Models\Room;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes();

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    $room = Room::findOrFail($roomId);

    return app(RoomServiceInterface::class)->userInRoom($user->id, $room);
});
