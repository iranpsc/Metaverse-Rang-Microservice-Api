<?php

namespace App\Http\Middleware;

use App\Events\UserStatusChanged;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Activity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {

            $request->user()->update(['last_seen' => now()]);

            broadcast(new UserStatusChanged([
                'id'     => $request->user()->id,
                'online' => true,
            ]));
        }

        return $next($request);
    }
}
