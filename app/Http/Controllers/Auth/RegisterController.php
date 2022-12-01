<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\Referal;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    /**
     * @param RegisterRequest $request
     * @param $referral
     * @return JsonResponse
     * @throws ValidationException
     */
    public function register(RegisterRequest $request, $referral = null): JsonResponse
    {
        $pass_pattern = "/^(?=.*[A-Z])(?=.*[a-z]).{8,}$/";

        if (!preg_match($pass_pattern, $request->password)) {
            throw ValidationException::withMessages([
                'error' => 'رمز عبور باید حداقل 8 کاراکتر شامل حداقل یک حرف کوچک، یک حرف بزرگ و یکی از سمبل های !@#$%^&* باشد'
            ]);
        }

        $code = $this->generateCitizenCode();

        $referralLink = $this->generateReferalLink($code);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'code' => $code,
            'referal_link' => $referralLink,
            'ip' => ""
        ]);

        if (isset($referral)) {
            $reference_user = User::firstWhere('code', $referral);
            if (is_null($reference_user)) {
                throw ValidationException::withMessages([
                    'error' => 'لینک رفرال صحیح نیست'
                ]);
            } else {
                Referal::create([
                    'reference_id' => $reference_user->id,
                    'referer_id' => $user->id,
                ]);
            }
        }
        $user->assets()->create();
        $user->settings()->create();
        $user->generalSettings()->create();
        $user->log()->create();
        $user->variables()->create();

        event(new Registered($user));

        return response()->json([
            'success' => 'ایمیلی جهت تایید حساب کاربری برایتان ارسال گردید'
        ]);
    }

    /**
     * @param $code
     * @return string
     */
    private function generateReferalLink($code): string
    {
        return config('app.url') . '/citizen/' . $code;
    }

    /**
     * @return string
     */
    private function generateCitizenCode(): string
    {
        $lastUser = User::latest()->first();

        if (isset($lastUser)) {
            $lastUserCode = $lastUser->code;
            $codeNum = explode('-', $lastUserCode)[1];
            $codeNum += 1;
            return 'hm-' . $codeNum;
        }

        return 'hm-2000000';
    }

    private function checkReferral($referral) {
        $pattern = '';
        $reference_user = User::firstWhere('code', $referral);
    }
}
