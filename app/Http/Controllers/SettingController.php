<?php

namespace App\Http\Controllers;

use App\Notifications\GetOtpNotification;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $settings = auth()->user()->settings;

        if ($request->has('checkout_days_count')) {
            $request->validate([
                'checkout_days_count' => 'required|numeric|min:3',
                'automatic_logout' => 'required|min:0',
            ]);

            $settings->update([
                'checkout_days_count' => $request->checkout_days_count,
                'automatic_logout' => $request->automatic_logout,
            ]);
        }

        if ($request->has('setting')) {
            $key = $request->input('setting');
            $value = $request->input('status');

            $settings->update([
                $key => $value,
            ]);
        }

        return response()->json([
            'success' => 'تنظیمات بروز رسانی شد'
        ]);
    }

    /**
     * @param Request $request
     * @return Response|Application|ResponseFactory
     */
    public function generalSettingsUpdate(Request $request): Response|Application|ResponseFactory
    {
        $setting = $request->input('setting');
        $status = $request->input('status');

        auth()->user()->generalSettings->update([
            $setting => $status,
        ]);
        return response('تنظیمات بروز رسانی شد', 200);
    }


    public function uploadProfilePhoto(Request $request)
    {
        $this->validate(
            $request,
            ['image' => 'required|file|mimes:png,jpg'],
            [
                'image.required' => 'تصویری برای بارگذاری انتخاب کنید',
                'image.mimes' => 'فرمت فایل صحیح نمی باشد'
            ]

        );
        $url = env('FTP_ENDPOINT') . $request->file('image')->store('/user/profile/' . $request->user()->id);
        $request->user()->profilePhoto()->updateOrCreate(
            ['imageable_id' => $request->user()->id],
            [
                'url' => $url
            ]
        );
        return response()->json([
            'message' => 'تصویر بارگذاری شد'
        ], 200);
    }

    public function sendPhoneVerificationOtp(Request $request)
    {
        if($request->user()->phone)
        {
            abort(403, 'کاربر قبلا شماره تلفن خود را ثبت کرده است');
        }

        if(Cache::has('verify-phone-'. $request->user()->id))
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
        Cache::put('verify-phone-'. $request->user()->id, $data, now()->addMinutes(5));
        $request->user()->notify(new GetOtpNotification($request->phone, $data['code']));

        return response()->json(['success' => 'کد تایید ارسال گردید'], 200);
    }

    public function verifyPhone(Request $request)
    {
        $this->validate(
            $request,
            ['code' => 'required|integer|numeric'],
            [
                'code.required' => 'کد تایید را وارد کنید',
                'code.integer' => 'کد تایید وارد شده صحیح نیست',
            ]
        );

        $cachedData = Cache::get('verify-phone-'. $request->user()->id);

        if (!$cachedData || $cachedData['code'] != $request->code) {
            abort(401, 'کد تایید وارد صحیح نیست');
        }

        $request->user()->update([
            'phone' => $cachedData['phone']
        ]);
        return response()->json([
            'success' => 'شماره تلفن ثبت شد'
        ]);
    }
}
