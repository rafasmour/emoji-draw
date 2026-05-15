<?php

namespace App\Casts;

use App\DataObjects\RoomStatus;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class RoomStatusCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): RoomStatus
    {
        $data = json_decode($value, true);

        return new RoomStatus(
            started: (bool) ($data['started'] ?? false),
            round: (int) ($data['round'] ?? 0),
            time: (string) ($data['time'] ?? ''),
            term: (string) ($data['term'] ?? ''),
            guesses: (int) ($data['guesses'] ?? 0),
        );
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return json_encode($value);
    }
}
