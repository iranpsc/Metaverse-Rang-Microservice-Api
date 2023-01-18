<?php

namespace App\Policies;

use App\Models\Level\Prize;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
class PrizePolicy
{
    use HandlesAuthorization;

    public function recievePrize(User $user, Prize $prize)
    {
        return $user->recievedPrizes->where('prize_id', $prize->id)->exists() ? false : true;
    }
}
