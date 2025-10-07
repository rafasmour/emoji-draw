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
        'canvasStrokes',
        'started',
    ];
    protected $casts = [
        'name' => 'string',
        'owner' => 'string',
        /*
         * users: [
         *   [
         *      'id' => 'string',
         *      'name' => 'string',
         *      'score' => 'int',
         *      'guesses' => 'int',
         *      'correct_guesses' => 'int',
         *      'drawings_guessed' => 'int',
         *      'artist' => 'boolean',
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
         *  'categories' => 'string[]',
         *  'difficulty' => 'string[]',
         *  'language' => 'string',
         *  'timeLimit' => 'int' in seconds,
         *   ...(more to come)
         * ]
         */
        'settings' => 'array',
        'canvasStrokes' => 'array',
        'started' => 'boolean',
    ];
    /** @use HasFactory<\Database\Factories\RoomFactory> */
    use HasFactory;
}
