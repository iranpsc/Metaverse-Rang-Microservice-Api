<?php

namespace App\Observers;

use App\Events\UserStatusChanged;
use App\Models\User;
use App\Notifications\LogedInNotification;
use Illuminate\Support\Facades\DB;
use App\Models\Levels\Level;
use App\Models\Variable;
use Illuminate\Auth\Events\Registered;
use App\Models\Referral;

class UserObserver
{
    public $afterCommit = true;

    /**
     * Handle the User "created" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function created(User $user)
    {
        $user->update(['email_verified_at' => now()]);

        $user->wallet()->create();

        $user->settings()->create();

        $user->log()->create();

        $user->variables()->create();

        $user->activities()->create([
            'start' => now(),
            'ip' => request()->ip(),
        ]);

        if (request()->referral) {
            $reference_user = User::where('code', request()->referral)->select('id')->first();

            Referral::create([
                'reference_id' => $reference_user->id,
                'referrer_id' => $user->id,
            ]);
        }

        event(new Registered($user));
    }

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

        $latestActivity->update([
            'end' => now(),
            'total' => $latestActivity->start->diffInMinutes(now()),
            'ip' => request()->ip(),
        ]);

        $user->hourReached();

        $user->update(['last_seen' => now()->subMinutes(2)]);

        $user->events()->create([
            'event' => 'خروج از حساب کاربری',
            'ip' => request()->ip(),
            'device' => request()->userAgent(),
            'status' => 1,
        ]);

        broadcast(new UserStatusChanged([
            'id'     => $user->id,
            'online' => false
        ]));
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

        $next_level = Level::where('score', '<=', $user->score)
            ->whereNotIn('id', $user->levels->pluck('id'))
            ->with('prize')->first();

        if ($next_level) {

            $user->levels()->attach($next_level->id);

            $levelPrize = $next_level->prize;

            if ($user->can('recievePrize', $levelPrize)) {
                $wallet = $user->wallet;
                $wallet->increment('psc', $levelPrize->psc);
                $wallet->increment('blue', $levelPrize->blue);
                $wallet->increment('red', $levelPrize->red);
                $wallet->increment('yellow', $levelPrize->yellow);
                $wallet->update(['effect' => $levelPrize->effect]);
                $wallet->increment('satisfaction', $levelPrize->satisfaction);
                $user->recievedLevelPrizes()->attach($levelPrize->id);
            }
        }
    }
}
