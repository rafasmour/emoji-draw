<?php


use App\Http\Controllers\Room\ChatController;
use App\Http\Controllers\Room\CreateRoomController;
use App\Http\Controllers\Room\GameActionController;
use App\Http\Controllers\Room\GameViewController;
use App\Http\Controllers\Room\GetRoomsController;
use App\Http\Controllers\Room\RoomEntranceController;
use App\Http\Controllers\Room\RoomLobbyController;
use App\Http\Controllers\Room\RoomSettingsController;
use App\Http\Middleware\EnsureUserInRoom;
use Illuminate\Support\Facades\Route;

Route::prefix('room')->middleware('auth')->group(function () {
    Route::get('/', [GetRoomsController::class, 'index'])->name('room.rooms');
    Route::post('create', [CreateRoomController::class, 'index'])->name('room.create');
    Route::post('join', [RoomEntranceController::class, 'join'])->name('room.join');
    // TODO: ->middleware('room-auth') using room specific token
    Route::prefix('{room}')->middleware(EnsureUserInRoom::class)->group(function () {
        Route::get('/', [RoomLobbyController::class, 'index'])->name('room.lobby');
        Route::get('game', [GameViewController::class, 'index'])->name('room.game');
        Route::post('leave', [RoomEntranceController::class, 'leave'])->name('room.leave');
        Route::get('messages', [ChatController::class, 'getMessages'])->name('room.messages');
        Route::post('messages', [ChatController::class, 'sendMessage'])->name('room.send.message');
        Route::get('settings', [RoomSettingsController::class, 'settings'])->name('room.settings');
        Route::patch('settings', [RoomSettingsController::class, 'updateSettings'])->name('room.update.settings');
        Route::get('canvas', [GameActionController::class, 'canvas'])->name('room.canvas');
        Route::post('canvas', [GameActionController::class, 'stroke'])->name('room.stroke');
        Route::post('guess', [GameActionController::class, 'guess'])->name('room.guess');
   });

});
