<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotGuest
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_guest) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
