<?php

namespace App\Policies;

use App\Models\ProfileLimitation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProfileLimitationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        $limitedUserId = request()->input('limited_user_id');

        return ProfileLimitation::where('limiter_user_id', $user->id)
            ->where('limited_user_id', $limitedUserId)->doesntExist();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProfileLimitation  $profileLimitation
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, ProfileLimitation $profileLimitation)
    {
        return $profileLimitation->limiterUser->is($user);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ProfileLimitation  $profileLimitation
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, ProfileLimitation $profileLimitation)
    {
        return $profileLimitation->limiterUser->is($user);
    }
}
