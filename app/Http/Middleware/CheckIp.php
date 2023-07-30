<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Cache;

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

        $ipAllowed = Cache::remember($request->ip(), 60 * 60 * 24, function () use ($request) {
            return app(Pipeline::class)
                ->send($request)
                ->through([
                    \App\Services\FilterIpRangeService::class,
                    \App\Services\FilterIpService::class
                ])
                ->thenReturn();
        });

        return $ipAllowed ? $next($request) : abort(403, 'UnAuthorized access location');
    }
}
