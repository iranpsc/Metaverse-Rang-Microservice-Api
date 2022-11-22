<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckOtp
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
        $feature_otp = $request->user()->featureOtp;
        if( $feature_otp->otp_off && $feature_otp->updated_at->addMinutes($feature_otp->time) > now()) {
            return $next($request);
         }
        return response()->json([
            'code' => 403,
            'message' => 'جهت ادامه شماره تلفن همراه خود را تایید کنید'
        ]);
    }
}
