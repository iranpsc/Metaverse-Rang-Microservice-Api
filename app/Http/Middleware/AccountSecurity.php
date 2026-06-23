<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;

class AccountSecurity
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
        if (!app()->isProduction()) return $next($request);

        if ($request->user()->hasConnectedWallet()) return $next($request);

        $accountSecurity = $request->user()->accountSecurity;

        if (is_null($accountSecurity) || time() > optional($accountSecurity)->until) {
            return $request->expectsJson() ?
                abort(410, 'جهت ادامه امنیت حساب کاربری خود را غیر فعال کنید!')
                : RouteServiceProvider::HOME;
        }

        return $next($request);
    }
}
