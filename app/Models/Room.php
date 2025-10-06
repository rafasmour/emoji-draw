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
        'users' => 'array',
        'settings' => 'array',
        'canvasStrokes' => 'array',
        'started' => 'boolean',
    ];
    /** @use HasFactory<\Database\Factories\RoomFactory> */
    use HasFactory;
}
