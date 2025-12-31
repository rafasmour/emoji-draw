<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class JsonToCollectionCast implements CastsAttributes
{
    public function __construct(protected string $dtoClass) {}

    public function get($model, $key, $value, $attributes): Collection
    {
        $data = is_string($value) ? json_decode($value, true) : $value;

        return collect($data ?? [])->map(fn ($item) => new $this->dtoClass(...$item));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        return $value instanceof Collection ? json_encode($value->toArray()) : $value;
    }
}
