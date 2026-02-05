<?php

namespace App\Models;

use App\Casts\JsonToCollectionCast;
use App\Casts\RoomSettingsCast;
use App\DataObjects\CanvasElement;
use App\DataObjects\ChatMessage;
use App\DataObjects\RoomSettings;
use App\DataObjects\RoomUser;
use Database\Factories\RoomFactory;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property Collection<int, RoomUser> $users
 * @property RoomSettings $settings
 * @property Collection<int, ChatMessage> $chat
 * @property Collection<int, CanvasElement> $canvas
 */
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
        'canvas',
        'started',
        'status',
    ];

    protected $casts = [
        'name' => 'string',
        'owner' => 'string',
        'artist' => 'string',
        'users' => JsonToCollectionCast::class.':'.RoomUser::class,
        'settings' => RoomSettingsCast::class,
        'chat' => JsonToCollectionCast::class.':'.ChatMessage::class,
        'started' => 'boolean',
        'status' => 'array',
        'canvas' => JsonToCollectionCast::class.':'.CanvasElement::class,
    ];

    /** @use HasFactory<RoomFactory> */
    use HasFactory;
}
