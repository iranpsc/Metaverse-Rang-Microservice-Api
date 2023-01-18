<?php

namespace App\Policies;

use App\Models\Dynasty\JoinRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class JoinRequestPolicy
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

    public function accept(User $user, JoinRequest $joinRequest)
    {
        return $joinRequest->toUser->is($user) && $joinRequest->status === 1 ;
    }

    public function reject(User $user, JoinRequest $joinRequest)
    {
        return $joinRequest->toUser->is($user) && $joinRequest->status === 1 ;
    }
}
