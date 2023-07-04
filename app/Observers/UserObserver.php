<?php

namespace App\Observers;

use App\Events\UserStatusChanged;
use App\Models\User;
use App\Notifications\LogedInNotification;
use Illuminate\Support\Facades\DB;
use App\Models\Level\UserLevel;
use App\Models\Level\Level;
use App\Models\Variable;
use Illuminate\Auth\Events\Registered;

class UserObserver
{
    /**
     * Handle the User "LogedIn" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function logedIn(User $user)
    {
        $user->events()->create([
            'event' => 'ورود به حساب کاربری',
            'ip' => request()->ip(),
            'device' => request()->userAgent(),
            'status' => 1,
        ]);

        $user->update(['last_seen' => now()]);

        $user->notify(new LogedInNotification(request()->ip()));

        $user->activities()->create([
            'start' => now(),
            'ip' => request()->ip(),
        ]);

        broadcast(new UserStatusChanged([
            'id'     => $user->id,
            'online' => true
        ]));
    }

    /**
     * Handle the User "updated" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function logedOut(User $user)
    {
        $latestActivity = $user->latestActivity;
        if (isset($latestActivity) && is_null($latestActivity->end)) {
            $start = $latestActivity->start;
            $total = $start->diffInMinutes(now());
            $latestActivity->update([
                'end' => now(),
                'total' => $total,
                'ip' => request()->ip(),
            ]);
            $user->hourReached();
        }
        $user->update(['last_seen' => now()->subMinutes(2)]);
        broadcast(new UserStatusChanged([
            'id'     => $user->id,
            'online' => false
        ]));
    }

    public function registered(User $user) {
        $user->assets()->create();
        $user->settings()->create();
        $user->generalSettings()->create();
        $user->log()->create();
        $user->variables()->create();
        createUserPrivacy($user);
        event(new Registered($user));
    }

    /**
     * Handle User Activity Hours Event
     *
     * @param \App\Models\User $user
     * @return void
     */
    public function hourReached(User $user): void
    {
        $totalActiveHours = $user->activities->sum('total');
        $user->log->update([
            'activity_hours' => ceil($totalActiveHours / 60) * 0.1
        ]);
        $this->calculateScore($user);
    }

    /**
     * Handle the User "followed" event.
     *
     * @param \App\Models\User $user
     * @return void
     */
    public function followed(User $user): void
    {
        $totalFollwers = $user->followers->count();
        $user->log->update([
            'followers_count' => $totalFollwers * 0.1
        ]);
        $this->calculateScore($user);
    }

    /**
     * Handle user trades events
     *
     * @param \App\Models\User $user
     * @return void
     */
    public function traded(User $user): void
    {
        $psc_value = Variable::getRate('psc');
        $psc_count = 7000000 / $psc_value;
        $trades = DB::table('trades')
            ->where('buyer_id', $user->id)
            ->where(function ($query) use ($psc_count) {
                $query->where('irr_amount', '>', 7000000)
                    ->orWhere('psc_amount', '>', $psc_count);
            })
            ->orWhere('seller_id', $user->id)
            ->where(function ($query) use ($psc_count) {
                $query->where('irr_amount', '>', 7000000)
                    ->orWhere('psc_amount', '>', $psc_count);
            })->count();

        $user->log->update([
            'transactions_count' => $trades * 2
        ]);
        $this->calculateScore($user);
    }

    /**
     * Handel user Deposit Events
     *
     * @param \App\Models\User $user
     * @return void
     */
    public function deposit(User $user): void
    {
        $amount = $user->latestPayment->amount;
        $user->log->increment('deposit_amount', $amount * 0.0001);
        $this->calculateScore($user);
    }

    private function calculateScore(User $user)
    {
        $log = $user->log;
        $sum = array_sum([
            $log->transactions_count,
            $log->followers_count,
            $log->deposit_amount,
            $log->activity_hours,
        ]);
        $log->update(['score' => $sum]);
        $user->update(['score' => $sum]);

        $next_level = null;

        foreach (Level::lazy() as $level) {
            if ($sum >= $level->score) {
                $next_level = $level;
            }
        }

        if (!$next_level) return;
        if ($sum >= $next_level->score) {
            UserLevel::updateOrCreate(
                ['user_id' => $user->id],
                ['level_id' => $next_level->id]
            );
            $prize = $next_level->prize;
            if ($user->can('recievePrize', $prize)) {
                $assets = $user->assets;
                $assets->increment('psc', $prize->psc);
                $assets->increment('blue', $prize->blue);
                $assets->increment('red', $prize->red);
                $assets->increment('yellow', $prize->yellow);
                $assets->update(['effect' => $prize->effect]);
                $assets->increment('satisfaction', $prize->satisfaction);
                $user->recievedPrizes()->create([
                    'prize_id' => $prize->id
                ]);
            }
        }
    }

}
