<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\AccountSecurityRequest;
use App\Notifications\GetOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyAccountSecurityRequest;

class AccountSecurityController extends Controller
{
    public function getVerifyCode(AccountSecurityRequest $request)
    {

        $user = $request->user();
        $accountSecurity = $user->accountSecurity;
        $code = random_int(100000, 999999);
        if ( is_null($accountSecurity)) {
            $accountSecurity = $user->accountSecurity()->create([
                'length' => $request->time * 60,
            ]);
        } else {
            $accountSecurity->update([
                'unlocked' => false,
                'until' => null,
                'length' => $request->time * 60,
            ]);
        }

        if (is_null($user->phone)) {
            $user->update(['phone' => $request->phone]);
        }

        $accountSecurity->otp()->updateOrCreate(
            ['user_id' => $user->id],
            ['code' => Hash::make($code)]
        );
        $user->notify(new GetOtpNotification($code, phone:$user->phone ?: $request->phone));
        return response()->noContent(200);
    }

    public function turnOffAccountSecurity(VerifyAccountSecurityRequest $request)
    {
        $user = $request->user();
        $accountSecurity = $user->accountSecurity;

        abort_if(!$accountSecurity || !$accountSecurity->otp, 400);
        abort_if($accountSecurity->unlocked, 400);

        if(Hash::check($request->code, $accountSecurity->otp->code)) {
            if(is_null($user->phone_verified_at)) {
                $user->update(['phone_verified_at' => now()]);
            }
            $accountSecurity->update([
                'unlocked' => true,
                'until' => time() + $accountSecurity->length,
            ]);
            $accountSecurity->otp->delete();
            $user->events()->create([
                'event' => "غیر فعال سازی امنیت حساب کاربری",
                'ip' => $request->ip(),
                'device' => $request->userAgent(),
                'status' => 1,
            ]);
            return response()->noContent(200);
        } else {
            throw ValidationException::withMessages([
                'code' => 'کد تایید صحیح نیست!'
            ]);
        }
    }
}
