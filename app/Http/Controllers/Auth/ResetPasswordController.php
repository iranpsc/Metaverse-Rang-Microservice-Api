<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use App\Notifications\GetOtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends Controller
{
    public function sendOtpCode(ResetPasswordRequest $request)
    {
        $user = $request->user();

        if (!Hash::check($request->input('old_password'), $user->password)) {
            throw ValidationException::withMessages([
                'error' => 'رمز عبور وارد شده صحیح نیست'
            ]);
        }

        $pass_pattern = "/^(?=.*[!@#$%^&*()])(?=.*[A-Z])(?=.*[a-z]).{8,}$/";

        if (!preg_match($pass_pattern, $request->password)) {
            throw ValidationException::withMessages([
                'error' => 'رمز عبور باید حداقل 8 کاراکتر شامل حداقل یک حرف کوچک، یک حرف بزرگ و یکی از سمبل های !@#$%^&* باشد'
            ]);
        }

        $data = [
            'code' => random_int(1000, 999999),
            'password' => $request->password,
        ];

        Cache::put('reset-password-' . $user->id, $data, now()->addMinutes(5));

        $user->notify(new GetOtpNotification(null, $data['code']));

        return response()->json(['success' => 'کد تاییدی برای شما ارسال گردیده است. لطفا آنرا جهت ادامه وارد کنید'], 200);
    }

    public function resetPassword(Request $request)
    {
        $this->validate(
            $request,
            ['code' => 'required|integer|numeric'],
            [
                'code.required' => 'کد تایید را وارد کنید',
                'code.integer' => 'کد تایید وارد شده صحیح نیست',
            ]
        );

        $cachedData = Cache::get('reset-password-' . $request->user()->id);

        if (!$cachedData || $cachedData['code'] != $request->code) {
            abort(401, 'کد تایید وارد صحیح نیست');
        }

        $request->user()->update([
            'password' => Hash::make($cachedData['password'])
        ]);
        return response()->json([
            'success' => 'رمز عبور تغییر داده شد'
        ]);
    }
}
