<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(LoginRequest $request): UserResource|ValidationException|JsonResponse
    {
        $user = User::firstWhere('email', $request->email);

        if (is_null($user)) {
            throw ValidationException::withMessages([
                'email' => 'کاربر با این آدرس ایمیل یافت نشد'
            ]);
        } else {
            if (RateLimiter::remaining($this->throttle($user), $perMinute = 3)) {
                RateLimiter::hit($this->throttle($user), 300);
                if (!Hash::check($request->password, $user->password)) {
                    throw ValidationException::withMessages([
                        'password' => 'رمز عبور اشتباه است'
                    ]);
                } else {
                    RateLimiter::clear($this->throttle($user));
                    $user->logedIn();
                    $user->token = $user->createToken('token-' . $user->id)->plainTextToken;
                    return new UserResource($user);
                }
            } else {
                $seconds = RateLimiter::availableIn($this->throttle($user));
                return response()->json([
                    'error' => sprintf('لطفا بعد از %s ثانیه دوباره تلاش کنید.', $seconds)
                ], 400);
            }
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        $request->user()->logedOut();
        return response()->noContent();
    }

    private function throttle(User $user): string
    {
        return implode('-', [$user->email, request()->ip(), $user->id]);
    }
}
