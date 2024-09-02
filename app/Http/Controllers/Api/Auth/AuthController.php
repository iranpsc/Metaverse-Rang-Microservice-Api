<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Exception\InvalidArgumentException;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\AuthenticatedUserResource;

class AuthController extends Controller
{
    /**
     * Register user
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $request->validate([
            'back_url' => 'required|url',
            'referral' => 'nullable|string|exists:users,code'
        ]);

        $query = http_build_query([
            'client_id' => config('app.oauth_client_id'),
            'redirect_uri' => route('auth.redirect'),
            'referral' => $request->referral,
            'back_url' => $request->back_url,
        ]);

        $url = config('app.oauth_server_url') . '/register?' . $query;

        return response()->json(['url' => $url]);
    }

    /**
     * Redirect user to the OAuth server
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function redirect(Request $request)
    {
        $request->validate([
            'redirect_to' => 'required|url',
        ]);

        cache()->put('state', $state = Str::random(40), now()->addMinutes(5));

        cache()->put(
            'redirect_to',
            $request->query('redirect_to'),
            now()->addMinutes(5)
        );

        $query = http_build_query([
            'client_id' => config('app.oauth_client_id'),
            'redirect_uri' => route('auth.callback'),
            'response_type' => 'code',
            'scope' => '',
            'state' => $state,
        ]);

        $url = config('app.oauth_server_url') . '/oauth/authorize?' . $query;

        return $request->expectsJson()
            ? response()->json(['url' => $url])
            : redirect()->away($url);
    }

    /**
     * Handle OAuth server callback
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        $state = cache()->pull('state');

        throw_unless(
            strlen($state) > 0 && $state === $request->state,
            InvalidArgumentException::class,
            'Invalid state value.'
        );

        $response = Http::asForm()->post(config('app.oauth_server_url') . '/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('app.oauth_client_id'),
            'client_secret' => config('app.oauth_client_secret'),
            'redirect_uri' => route('auth.callback'),
            'code' => $request->code,
        ]);

        $user = Http::withHeaders([
            'Authorization' => 'Bearer ' . $response['access_token'],
        ])->get(config('app.oauth_server_url') . '/api/user');

        $user = User::updateOrCreate(
            ['email' => $user['email']],
            [
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['mobile'],
                'email_verified_at' => now(),
                'password' => Hash::make(Str::random(10)),
                'referral' => $user['referral'],
                'code' => $user['code'],
                'ip' => $request->ip(),
                'access_token' => $response['access_token'],
                'refresh_token' => $response['refresh_token'],
                'expires_in' => $response['expires_in'],
                'token_type' => $response['token_type'],
            ]
        );

        $this->guard()->login($user);

        $request->session()->regenerate();

        return $this->authenticated($request, $user);
    }

    /**
     * Get after authentication response data
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\User $user
     */
    protected function authenticated(Request $request, $user)
    {
        $user->logedIn();

        $user->load('settings:id,user_id,automatic_logout');

        $automaticLogout = $user->settings->automatic_logout;

        $tokenExpiresAt = now()->addMinutes($automaticLogout ?: 55);

        $token = $user->createToken('token_' . $user->id, expiresAt: $tokenExpiresAt)->plainTextToken;
        $token = explode('|', $token)[1];

        $query = http_build_query([
            'token' => $token,
            'expires_at' => now()->diffInMinutes($tokenExpiresAt),
        ]);

        $url = cache()->pull('redirect_to') . '/?' . $query;

        return redirect()->away($url);
    }

    /**
     * Logout user
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        $request->user()->logedOut();

        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    /**
     * Get authenticated user data
     *
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\AuthenticatedUserResource
     */
    public function me(Request $request)
    {
        $user = $request->user();

        $user->load([
            'settings:id,user_id,automatic_logout',
            'profilePhotos',
            'kyc:id,user_id,status,birthdate',
            'unreadNotifications'
        ]);

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
