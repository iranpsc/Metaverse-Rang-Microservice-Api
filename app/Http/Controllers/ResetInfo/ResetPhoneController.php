<?php

namespace App\Http\Controllers\ResetInfo;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetInfoRequest;
use App\Models\Reset;
use Illuminate\Http\Request;
use App\Notifications\GetOtpNotification;
use Illuminate\Support\Facades\Hash;

class ResetPhoneController extends Controller
{
    public function sendVerifyCode(ResetInfoRequest $request)
    {
        $user = $request->user();
        $reset = Reset::create([
            'user_id' => $user->id,
            'type' => 'phone',
            'value' => $request->phone,
        ]);
        $code = random_int(100000, 999999);
        $reset->otp()->create([
            'user_id' => $user->id,
            'code' => Hash::make($code)
        ]);
        $user->notify(new GetOtpNotification($code));
        return response()->json(['message' => 'کد تایید ارسال گردید. جهت ادامه کد تایید را وارد کنید.'], 200);

    }

    public function verify(Request $request)
    {
        $this->validate(
            $request,
            ['code' => 'required|integer|numeric'],
            [
                'code.required' => 'کد تایید را وارد کنید',
                'code.integer' => 'کد تایید وارد شده صحیح نیست',
            ]
        );
        $user = $request->user();
        $reset = $user->latestResetRequest;
        if(is_null($reset) || $reset->verified == 1) {
            abort(401, 'Invalid Request');
        }

        if(Hash::check($request->code, $reset->otp->code)) {
            $user->update([
                'phone' => $reset->value,
                'phone_verified_at' => now(),
            ]);
            $reset->update(['verified' => true]);
            $reset->otp->delete();
            return response()->json(['success' => 'شماره تلفن تغییر کرد'], 200);
        }
        return response()->json(['success' => 'کد تایید صحیح نمی باشد یا منقضی شده است!'], 404);
    }

}
