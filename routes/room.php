<?php


use App\Http\Controllers\Room\ChatController;
use App\Http\Controllers\Room\CreateRoomController;
use App\Http\Controllers\Room\GameActionController;
use App\Http\Controllers\Room\GetRoomsController;
use App\Http\Controllers\Room\JoinLeaveController;
use App\Listeners\RoomSettings;
use Illuminate\Support\Facades\Route;

Route::prefix('room')->middleware('auth')->group(function () {
    Route::get('/', [GetRoomsController::class])->name('room.getRooms');
    Route::post('create', [CreateRoomController::class, 'create'])->name('room.create');
    Route::prefix('{room}')->group(function () {
        Route::post('join', [JoinLeaveController::class, 'join'])->name('room.join');
        Route::post('leave', [JoinLeaveController::class, 'leave'])->name('room.leave');
        Route::get('messages', [ChatController::class, 'getMessages'])->name('room.messages');
        Route::post('messages', [ChatController::class, 'sendMessage'])->name('room.send.message');
        Route::get('users', [JoinLeaveController::class, 'users'])->name('room.users');
        Route::get('settings', [RoomSettings::class, 'settings'])->name('room.settings');
        Route::patch('settings', [RoomSettings::class, 'updateSettings'])->name('room.update.settings');
        Route::get('canvas', [GameActionController::class, 'canvas'])->name('room.canvas');
        Route::post('canvas', [GameActionController::class, 'canvas'])->name('room.draw');
        Route::get('guesses', [GameActionController::class, 'guess'])->name('room.guesses');
        Route::post('guess', [GameActionController::class, 'guess'])->name('room.guess');
        Route::post('start', [GameActionController::class, 'start'])->name('room.start');
        Route::post('stop', [GameActionController::class, 'stop'])->name('room.stop');
        Route::get('started', [GameActionController::class, 'started'])->name('room.started');

   });

});
