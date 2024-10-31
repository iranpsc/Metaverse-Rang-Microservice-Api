<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ProfileLimitation;
use Illuminate\Support\Facades\Auth;

class CheckProfileLimitation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        $user = $request->route('user');

        if($user->id == Auth::id()) {
            return $next($request);
        }

        $profileLimitation = ProfileLimitation::where('limited_user_id', Auth::id())
            ->where('limiter_user_id', $user->id)
            ->first();

        $request->attributes->set('profileLimitation', $profileLimitation);

        return $next($request);
    }
}
