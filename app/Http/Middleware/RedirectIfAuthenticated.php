<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            $user = Auth::guard($guard)->user();
            if ($user && ! $user->is_guest) {
                return redirect($this->defaultRedirectUri());
            }
        }

        return $next($request);
    }

    protected function defaultRedirectUri(): string
    {
        foreach (['dashboard', 'home'] as $route) {
            if (Route::has($route)) {
                return route($route);
            }
        }

        return '/';
    }
}
