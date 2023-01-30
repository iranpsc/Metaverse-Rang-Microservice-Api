<?php

namespace App\Http\Controllers\Api\V1\ResetInfo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\ResetInfoRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\Reset;
use App\Notifications\GetOtpNotification;
class ResetEmailController extends Controller
{
    public function sendVerifyCode(ResetInfoRequest $request)
    {
        $user = $request->user();
        $reset = Reset::create([
            'user_id' => $user->id,
            'type' => 'email',
            'value' => $request->email,
        ]);
        $code = random_int(100000, 999999);
        $reset->otp()->create([
            'user_id' => $user->id,
            'code' => Hash::make($code)
        ]);
        $user->notify(new GetOtpNotification($code, 'mail', email: $request->email));
        return response()->noContent(200);

    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|integer']);
        $user = $request->user();
        $reset = $user->latestResetRequest;

        abort_if(is_null($reset) || $reset->verified == 1, 401, 'Not Valid');

        if(Hash::check($request->code, $reset->otp->code)) {
            $user->update([
                'email' => $reset->value,
                'email_verified_at' => now(),
            ]);
            $reset->update(['verified' => true]);
            $reset->otp->delete();
            return response()->noContent(200);
        }
        return response()->noContent(400);
    }
}
