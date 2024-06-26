<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\AuthenticatedUserResource;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function register(RegisterUserRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return $this->registered($request, $user);
    }

    protected function registered(RegisterUserRequest $request, $user)
    {
        $automaticLogout = $user->settings->automatic_logout;

        $user->automaticLogout = $automaticLogout;

        $tokenExpiresAt = now()->addMinutes($automaticLogout > 0 ? $automaticLogout : 60);

        $user->token = $user->createToken('token-' . $user->id, expiresAt: $tokenExpiresAt)->plainTextToken;

        $this->guard()->login($user);

        $request->session()->regenerate();

        return new AuthenticatedUserResource($user);
    }

    /**
     * Get the guard to be used during registration.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard('web');
    }
}
