<?php

namespace App\Observers;

use App\Models\Level\Level;
use App\Models\User;
use App\Models\Variable;
use Illuminate\Support\Facades\DB;
use App\Models\Level\UserLevel;

class UserLogObserver
{
    /**
     * Handle User Activity Hours Event
     *
     * @param \App\Models\User $user
     * @return void
     */
    public function hourReached(User $user): void
    {
        $totalActiveHours = $user->activies->sum('total');
        if ($totalActiveHours % 60 == 0) {
            $user->log->update([
                'activity_hours' => $totalActiveHours * 0.1
            ]);
        }
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
        $amount = $user->latestTransaction->amount;
        $user->log->increment('deposit_amount', $amount * 0.0001);
        $this->calculateScore($user);
    }

    private function calculateScore(User $user)
    {
        $log = $user->log;
        $sum = array_sum([
            $log->followers_count,
            $log->transactions_count,
            $log->acitivity_hours,
            $log->deposit_amount,
        ]);
        $log->increment('score', $sum);
        $user->increment('score', $sum);

        $next_level = null;

        foreach(Level::lazy() as $level)
        {
            if($sum >= $level->score)
            {
                $next_level = $level;
            }
        }

        if(! $next_level) return;
        if ($sum >= $next_level->score)
        {
            UserLevel::updateOrCreate(
                ['user_id' => $user->id],
                ['level_id' => $next_level->id]
            );
            $prize = $next_level->prize;
            if ($user->can('recievePrize', $prize))
            {
                $assets = $user->assets;
                $assets->increment('psc', $prize->psc);
                $assets->increment('blue', $prize->blue);
                $assets->increment('red', $prize->red);
                $assets->increment('yellow', $prize->yellow);
                $assets->increment('effect', $prize->effect);
                $assets->increment('satisfaction', $prize->satisfaction);
                $user->recievedPrizes()->create([
                    'prize_id' => $prize->id
                ]);
            }
        }
    }
}
