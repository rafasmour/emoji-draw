<?php

namespace App\Models;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Room extends Model
{
    use InteractsWithSockets;

    protected $connection = 'mongodb';

    protected $fillable = [
        'name',
        'owner',
        'users',
        'settings',
        'chat',
        'canvasStrokes',
        'started',
        'status',
    ];
    protected $casts = [
        'name' => 'string',
        'owner' => 'string',
        'artist' => 'string',
        /*
         * users: [
         *   [
         *      'id' => 'string',
         *      'name' => 'string',
         *      'score' => 'int',
         *      'guesses' => 'int',
         *      'correct_guesses' => 'int',
         *      'drawings_guessed' => 'int',
         *      'room_token' => 'string',
         *      ...(more to come)
         *   ]
         * ]
         */
        'users' => 'array',
        /*
         * settings: [
         *  'difficulty' => 'string',
         *  'public' => 'boolean',
         *  'cap' => 'int',
         *  'rounds' => 'int',
         *  'categories' => 'string[]',
         *  'difficulty' => 'string[]',
         *  'language' => 'string',
         *  'timeLimit' => 'int' in seconds,
         *   ...(more to come)
         * ]
         */
        'settings' => 'array',
        /*
         * chat: [
         *   'user_id' => 'string',
         *   'user_name' => 'string',
         *   'message' => 'string',
         * ]
         */
        'chat' => 'array',
        'started' => 'boolean',
        /*
         * status: [
         *  'round' => 'int',
         *  'time' => 'int',
         *  'term' => 'string',
         *  'started' => 'bool',
         * ]
         */
        'status' => 'array',
    ];
    /** @use HasFactory<\Database\Factories\RoomFactory> */
    use HasFactory;
}
