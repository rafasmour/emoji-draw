<?php

namespace App\Http\Middleware;

use App\UserInRoom;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserInRoom
{
    use UserInRoom;

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $room = $request->route('room');
        if (!$this->userInRoom($request->user()->getKey(), $room)) {
            return redirect()->route('room.rooms');
        }
        return $next($request);
    }
}
