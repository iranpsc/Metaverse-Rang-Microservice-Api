<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
        $response = Http::post(env('ADMIN_PANEL_URL').'api/check/ip', ['ip' => $request->ip()]);

        if ($response->successful()) {
            $response = $response->json();
            dd($response);
            if($response['code'] == '200') {
                return $next($request);
            } else {
                abort(403, 'Access Denied');
            }
        } else {
            abort(403, 'Access Denied');
        }
    }
}
