<?php

use App\Http\Middleware\EnsureUserInRoom;
use App\Models\Room;
use App\UserInRoom;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middlware' => ['auth:sanctum']]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    $room = Room::findOrFail($roomId);
    $userTrait = new EnsureUserInRoom();
    return $userTrait->userInRoom($user->id, $room);
});


