<?php

namespace App\Providers;

use App\Http\Contracts\GameServiceInterface;
use App\Http\Contracts\RoomEntranceServiceInterface;
use App\Http\Contracts\RoomOwnerServiceInterface;
use App\Http\Service\GameService;
use App\Http\Service\RoomEntranceService;
use App\Http\Service\RoomOwnerService;
use App\Models\Room;
use Illuminate\Support\ServiceProvider;
use Route;
use URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RoomEntranceServiceInterface::class, RoomEntranceService::class);
        $this->app->bind(GameServiceInterface::class, GameService::class);
        $this->app->bind(RoomOwnerServiceInterface::class, RoomOwnerService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceScheme('https');
        Route::model('room', Room::class);
    }
}
