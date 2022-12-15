<?php

namespace App\Http\Controllers\Auth;

use App\Events\LogedIn;
use App\Events\UserStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * @param LoginRequest $request
     * @return UserResource|JsonResponse
     * @throws ValidationException
     */
    public function login(LoginRequest $request): UserResource|JsonResponse
    {
        $user = User::firstWhere('email', $request->email);

        if (is_null($user)) {
            throw ValidationException::withMessages([
                'error' => 'کاربر با این آدرس ایمیل یافت نشد'
            ]);
        } else {
            if (RateLimiter::remaining($this->throttle($user), $perMinute = 3)) {
                RateLimiter::hit($this->throttle($user), 300);
                if (!Hash::check($request->password, $user->password)) {
                    $user->events()->create([
                        'event' => 'ورود به حساب کاربری',
                        'ip' => $request->ip(),
                        'device' => $request->userAgent(),
                        'status' => 0,
                    ]);
                    throw ValidationException::withMessages([
                        'email' => 'رمز عبور اشتباه است'
                    ]);
                } else {

                    RateLimiter::clear($this->throttle($user));
                    $user->update(['last_seen' => now()]);
                    $user->token = $user->createToken('token-' . $user->id)->plainTextToken;
                    $user->ip = $request->ip();
//                    dd($user->token);
                    LogedIn::dispatch($user);

                    broadcast(new UserStatusChanged([
                        'code' => $user->code,
                        'status' => 'online'
                    ]));

                    $user->events()->create([
                        'event' => 'ورود به حساب کاربری',
                        'ip' => $request->ip(),
                        'device' => $request->userAgent(),
                        'status' => 1,
                    ]);


                    return new UserResource($user);
                }
            } else {
                $seconds = RateLimiter::availableIn($this->throttle($user));
                return response()->json([
                    'error' => 'خطایی رخ داده است. لطفا بعد از ' . $seconds . 'تلاش کنید'
                ]);
            }
        }
    }

    /**
     * @param User $user
     * @return string
     */
    private function throttle(User $user): string
    {
        return Str::random(10) . '_' . $user->email;
    }

    /**
     * @param Request $request
     * @return Response|Application|ResponseFactory
     */
    public function logout(Request $request): Response|Application|ResponseFactory
    {
        $latestActivity = $request->user()->latestActivity;
        if (isset($latestActivity) && is_null($latestActivity->end)) {
            $start = Carbon::parse($latestActivity->start);
            $end = now();

            $total = $start->diffInMinutes($end);

            $latestActivity->update([
                'end' => $end,
                'total' => $total,
                'ip' => $request->ip(),
            ]);
            $request->user()->hourReached();
        }
        $request->user()->update(['last_seen' => now()->subMinutes(2)]);
        $request->user()->tokens()->delete();
        broadcast(new UserStatusChanged([
            'code' => $request->user()->code,
            'status' => 'offline'
        ]));
        return response('شما با موفقیت خارج شدید', 200);
    }
}
