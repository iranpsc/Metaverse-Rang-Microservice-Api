<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Level\UserActivity as Activity;
use Illuminate\Support\Carbon;

class UserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $latestActivity = $request->user()->latestActivity;
            if ($request->has('online') && $request->query('online') === 'true') {
                if (isset($latestActivity) && isset($latestActivity->end)) {
                    Activity::create([
                        'user_id' => $request->user()->id,
                        'start' => now(),
                        'ip' => $request->ip(),
                    ]);
                }
            }

            if ($request->has('online') && $request->query('online') === 'false') {
                if (isset($latestActivity) && empty($latestActivity->end)) {
                    $start = Carbon::parse($latestActivity->start);
                    $end = now();

                    $total = $start->diffInMinutes($end);

                    $latestActivity->update([
                        'end' => $end,
                        'total' => $total,
                    ]);
                    $user = $request->user();
                    $user->hourReached();
                }
            }
            $request->user()->update(['last_seen' => now()]);
        }

        return $next($request);
    }
}
