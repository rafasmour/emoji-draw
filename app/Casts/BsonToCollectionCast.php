<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BsonToCollectionCast implements CastsAttributes
{
    public function __construct(protected string $dtoClass) {}

    public function get($model, $key, $value, $attributes): Collection
    {
        $data = is_string($value) ? json_decode($value, true) : $value;

        return collect($data ?? [])->map(fn ($item) => new $this->dtoClass(...(array) $item));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        return (object) [...$value];
    }
}
