<?php

namespace App\Providers;

use App\Contracts\RoomServiceInterface;
use App\Contracts\TermServiceInterface;
use App\Contracts\UserServiceInterface;
use App\Http\Service\RoomService;
use App\Http\Service\TermService;
use App\Http\Service\UserService;
use App\Http\Contracts\ChatServiceInterface;
use App\Http\Contracts\GameActionServiceInterface;
use App\Http\Contracts\GameServiceInterface;
use App\Http\Contracts\RoomEntranceServiceInterface;
use App\Http\Contracts\RoomOwnerServiceInterface;
use App\Http\Contracts\RoomSettingsServiceInterface;
use App\Http\Service\ChatService;
use App\Http\Service\GameActionService;
use App\Http\Service\GameService;
use App\Http\Service\RoomEntranceService;
use App\Http\Service\RoomOwnerService;
use App\Http\Service\RoomSettingsService;
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
        $this->app->bind(ChatServiceInterface::class, ChatService::class);
        $this->app->bind(RoomSettingsServiceInterface::class, RoomSettingsService::class);
        $this->app->bind(GameActionServiceInterface::class, GameActionService::class);
        $this->app->bind(RoomServiceInterface::class, RoomService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(TermServiceInterface::class, TermService::class);
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
