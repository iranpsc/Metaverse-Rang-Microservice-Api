<?php

namespace App\Policies;

use App\Models\Kyc;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class KycPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Kyc  $kyc
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Kyc $kyc)
    {
        return $kyc->user->is($user);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return is_null($user->kyc);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Kyc  $kyc
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Kyc $kyc)
    {
        return $kyc->user->is($user) && $kyc->rejected();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Kyc  $kyc
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Kyc $kyc)
    {
        return false;
    }
}
