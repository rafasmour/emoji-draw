<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class JsonToCollectionCast implements CastsAttributes
{
    public function __construct(protected string $dtoClass) {}

    public function get($model, $key, $value, $attributes): Collection
    {
        $data = is_string($value) ? json_decode($value, true) : $value;

        return Collection::make($data ?? [])->map(fn ($item) => new $this->dtoClass(...$item));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        return [$key => $value instanceof Collection ? $value->toArray() : $value];
    }
}
