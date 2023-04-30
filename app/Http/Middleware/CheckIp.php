<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\App;

class CheckIp
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
        if(app()->isLocal()) return $next($request);

        $ipAllowed = app(Pipeline::class)
            ->send($request)
            ->through([
                \App\Services\FilterIpRangeService::class,
                \App\Services\FilterIpService::class
            ])
            ->thenReturn();
<<<<<<< HEAD
        return $ipAllowed || App::isLocal()
            ? $next($request)
            : abort(403, 'UnAuthorized');
=======
        return $ipAllowed ? $next($request) : abort(401, 'UnAuthorized');
>>>>>>> main
    }
}
