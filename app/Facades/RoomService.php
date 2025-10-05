<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \App\Http\Service\RoomService
 */
class RoomService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Http\Service\RoomService::class;
    }
}
