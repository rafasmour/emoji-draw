<?php

namespace App\Casts;

use App\DataObjects\RoomSettings;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class RoomSettingsCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): RoomSettings
    {
        $data = json_decode($value, true);

        return new RoomSettings(...$data);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        return json_encode($value);
    }
}
