<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Room;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'ensure.authenticated' => \App\Http\Middleware\EnsureAuthenticated::class,
            'ensure.not.guest' => \App\Http\Middleware\EnsureNotGuest::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        ]);
    })
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']],
    )
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if ($exception->getModel() !== Room::class) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json(['redirect' => route('room.rooms')]);
            }

            return redirect()->route('room.rooms');
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->is('room/*')) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json(['redirect' => route('room.rooms')]);
            }

            return redirect()->route('room.rooms');
        });
    })
    ->create();
