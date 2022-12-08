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
    public function register(RegisterRequest $request): JsonResponse
    {
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

        if ($request->has('referral')) {
            $reference_user = User::firstWhere('code', $request->referral);
            Referal::create([
                'reference_id' => $reference_user->id,
                'referer_id' => $user->id,
            ]);
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
}
