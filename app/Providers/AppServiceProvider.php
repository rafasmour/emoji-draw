<?php

namespace App\Providers;

use App\Jobs\RoundHandler;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceScheme('https');
        Route::model('room', Room::class);
        $this->app->bindMethod([RoundHandler::class, 'handle'], function (RoundHandler $job) {
            return $job->handle();
        });
    }
}
