<?php

namespace App\Models;

use App\Casts\BsonToCollectionCast;
use App\Casts\BsonToDTO;
use App\DataObjects\CanvasElement;
use App\DataObjects\ChatMessage;
use App\DataObjects\RoomSettings;
use App\DataObjects\RoomStatus;
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
        'status',
        'artist',
        'id',
    ];

    protected $casts = [
        'name' => 'string',
        'owner' => 'string',
        'artist' => 'string',
        'users' => BsonToCollectionCast::class.':'.RoomUser::class,
        'settings' => BsonToDTO::class.':'.RoomSettings::class,
        'chat' => BsonToCollectionCast::class.':'.ChatMessage::class,
        'status' => BsonToDTO::class.':'.RoomStatus::class,
        'canvas' => BsonToCollectionCast::class.':'.CanvasElement::class,
    ];

    /** @use HasFactory<RoomFactory> */
    use HasFactory;
}
