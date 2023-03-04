<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\LazyCollection;

class UserRepository extends Repository
{
    public function getTopPlayers()
    {
        return User::orderBy('score', 'DESC')
            ->take(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'code' => $user->code,
                    'profile_photos' => [$user->profilePhotos->last()],
                    'level' => $user->level,
                    'online' => $user->last_seen->diffInMinutes(now()) < 2,
                ];
            });
    }
}
