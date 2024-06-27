<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthenticatedUserResource;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use ThrottlesLogins, AuthenticatesUsers;

    protected $maxAttempts = 3;
    protected $decayMinutes = 5;

    protected function authenticated(Request $request, $user)
    {
        $user->logedIn();

        $user->load([
            'settings:id,user_id,automatic_logout',
            'latestProfilePhoto',
            'level',
            'kyc:id,user_id,status,birthdate',
            'variables',
        ])
            ->loadCount([
                'notifications as unreadNotifications_count' => function ($query) {
                    $query->where('read_at', null);
                },
            ]);

        $automaticLogout = $user->settings->automatic_logout;

        $user->automaticLogout = $automaticLogout;

        $tokenExpiresAt = now()->addMinutes($automaticLogout > 0 ? $automaticLogout : 60);

        $user->token = $user->createToken('token-' . $user->id, expiresAt: $tokenExpiresAt)->plainTextToken;

        return new AuthenticatedUserResource($user);
    }

    protected function loggedOut(Request $request)
    {
        $request->user()->tokens()->delete();
        $request->user()->logedOut();
        return response()->noContent();
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard('web');
    }
}
