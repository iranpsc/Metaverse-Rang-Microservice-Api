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
            'redirect_to' => 'nullable|url',
            'back_url' => 'nullable|url',
        ]);

        cache()->put('state', $state = Str::random(40), now()->addMinutes(5));

        cache()->put(
            'redirect_to',
            $request->query('redirect_to'),
            now()->addMinutes(5)
        );

        cache()->put(
            'back_url',
            $request->query('back_url'),
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

        $ssoUser = Http::withHeaders([
            'Authorization' => 'Bearer ' . $response['access_token'],
        ])->get(config('app.oauth_server_url') . '/api/user');

        $ssoUser = $ssoUser->json();

        if (!empty($ssoUser['email'])) {
            $identifierKey = 'email';
            $identifierValue = $ssoUser['email'];
        } elseif (!empty($ssoUser['wallet_address'])) {
            $identifierKey = 'wallet_address';
            $identifierValue = strtolower($ssoUser['wallet_address']);
        } else {
            throw new InvalidArgumentException('SSO user response must include email or wallet_address.');
        }

        $user = User::updateOrCreate(
            [$identifierKey => $identifierValue],
            [
                'name' => $ssoUser['name'] ?? 'User_' . substr($identifierValue, 2, 6),
                'email' => $ssoUser['email'] ?? null,
                'wallet_address' => !empty($ssoUser['wallet_address'])
                    ? strtolower($ssoUser['wallet_address'])
                    : null,
                'phone' => $ssoUser['mobile'] ?? null,
                'password' => Hash::make(Str::random(10)),
                'code' => $ssoUser['code'] ?? $this->generateUniqueCode(),
                'ip' => $request->ip(),
                'referrer_id' => $this->getReferrerId($ssoUser['referral'] ?? null),
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

        $automaticLogout = $user->settings?->automatic_logout;

        $tokenExpiresAt = now()->addMinutes($automaticLogout ?: 55);

        $token = $user->createToken('token_' . $user->id, expiresAt: $tokenExpiresAt)->plainTextToken;
        $token = explode('|', $token)[1];

        $query = http_build_query([
            'token' => $token,
            'expires_at' => now()->diffInMinutes($tokenExpiresAt),
        ]);

        $redirectTo = cache()->pull('redirect_to');
        $backUrl = cache()->pull('back_url');

        $url = ($redirectTo ?: $backUrl) . '/?' . $query;

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
            'kyc:id,user_id,fname,lname,status,birthdate',
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

    /**
     * Get referrer id
     *
     * @param string $code
     * @return int|null
     */
    protected function getReferrerId($code)
    {
        return $code ? User::where('code', $code)->value('id') : null;
    }

    /**
     * Generate a unique user code.
     */
    protected function generateUniqueCode(): string
    {
        do {
            $code = 'hm-' . random_int(2000000, 9999999);
        } while (User::where('code', $code)->exists());

        return $code;
    }
}
