<?php

namespace App\Http\Controllers\ResetInfo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Notifications\GetOtpNotification;

class ResetPhoneController extends Controller
{
    public function sendOtpToOldPhone(Request $request)
    {

        if(Cache::has('reset-phone-old-phone-verification-'. $request->user()->id))
        {
            abort(403, 'کد تایید قبلا ارسال شده است');
        }

        $this->validate(
            $request,
            [
                'phone' => 'required|ir_mobile',
            ],
            [

                'phone.required' => 'شماره تلفن را وارد کنید',
                'phone.ir_mobile' => 'شماره تلفن صحیح نمی باشد'
            ]
        );

        $data = [
            'phone' => $request->phone,
            'code' => random_int(100000, 999999)
        ];
        Cache::put('reset-phone-old-phone-verification-'. $request->user()->id, $data, now()->addMinutes(5));
        $request->user()->notify(new GetOtpNotification(null, $data['code']));

        return response()->json(['success' => 'کد تایید به شماره تلفن قبلی ارسال گردید'], 200);
    }

    public function verifyOldPhoneOtp(Request $request)
    {
        $this->validate(
            $request,
            ['code' => 'required|integer|numeric'],
            [
                'code.required' => 'کد تایید را وارد کنید',
                'code.integer' => 'کد تایید وارد شده صحیح نیست',
            ]
        );

        $cachedData = Cache::get('reset-phone-old-phone-verification-'. $request->user()->id);

        if (!$cachedData || $cachedData['code'] != $request->code) {
            abort(401, 'کد تایید وارد صحیح نیست');
        }

        $data = [
            'phone' => $cachedData['phone'],
            'code' => random_int(100000, 999999)
        ];
        Cache::put('new-phone-verification-'. $request->user()->id, $data, now()->addMinutes(5));

        Cache::forget('reset-phone-old-phone-verification-'. $request->user()->id);
        $request->user()->notify(new GetOtpNotification($cachedData['phone'], $data['code']));
        return response()->json(['success' => 'کد تاییدی به شماره تلفن همراه جدید ارسال گردید'], 200);
    }

    public function verifyNewPhoneOtp(Request $request)
    {
        $this->validate(
            $request,
            ['code' => 'required|integer|numeric'],
            [
                'code.required' => 'کد تایید را وارد کنید',
                'code.integer' => 'کد تایید وارد شده صحیح نیست',
            ]
        );

        $cachedData = Cache::get('new-phone-verification-'. $request->user()->id);

        if (!$cachedData || $cachedData['code'] != $request->code) {
            abort(401, 'کد تایید وارد صحیح نیست');
        }

        $request->user()->update([
            'phone' => $cachedData['phone']
        ]);

        $request->user()->resetPhone()->updateOrCreate([
            'count' => 1
        ]);

        return response()->json(['success' => 'شماره تلفن تغییر کرد'], 200);
    }

}
