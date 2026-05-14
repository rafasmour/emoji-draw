<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            $guest = User::create([
                'name'        => 'Guest_' . Str::upper(Str::random(6)),
                'email'       => 'guest_' . Str::uuid() . '@guest.local',
                'password'    => null,
                'is_guest'    => true,
                'preferences' => ['volume' => 100, 'mute' => false],
            ]);

            Auth::login($guest);
            $request->session()->regenerate();
        }

        return $next($request);
    }
}
