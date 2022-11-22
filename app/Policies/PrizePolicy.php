<?php

namespace App\Policies;

use App\Models\Level\Prize;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PrizePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function recievePrize(User $user, Prize $prize)
    {
        return $user->recievedPrizes->where('prize_id', $prize->id)->first() ? false : true;
    }
}
