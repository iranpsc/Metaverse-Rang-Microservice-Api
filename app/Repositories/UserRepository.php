<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends Repository
{
    /**
     * Retrieve the top users based on their score.
     *
     * @return \Illuminate\Support\Collection
     */
    public function topUsers()
    {
        return User::orderByDesc('score')
            ->with('latestProfilePhoto', 'level')
            ->take(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'image' => optional($user->latestProfilePhoto)->url,
                    'online' => $user->last_seen->diffInMinutes(now()) < 2,
                    'level' => optional($user->level)->slug,
                    'code' => $user->code,
                ];
            });
    }
}
