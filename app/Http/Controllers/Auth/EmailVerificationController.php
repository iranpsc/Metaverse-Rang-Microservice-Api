<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function verify(Request $request)
    {
        $user = User::find($request->route('id'));

        if ($user->hasVerifiedEmail()) {
            return redirect()->to('https://rgb.irpsc.com/metaverse/email?status=already_verified');
        } else if (!$request->hasValidSignature()) {
            return redirect()->to('https://rgb.irpsc.com/metaverse/email?status=invalid_link');
        }
        $user->markEmailAsVerified();
        $code = $this->generateCitizenCode();
        $user->update([
            'ip' => $request->ip(),
            'code' => $code,
            'referal_link' => $this->generateReferalLink($code)
        ]);
        return redirect()->to('https://rgb.irpsc.com/metaverse/email?status=verified');
    }

        /**
     * @param $code
     * @return string
     */
    private function generateReferalLink($code): string
    {
        return 'https://rgb.irpsc.com/citizen/' . $code;
    }

    /**
     * @return string
     */
    private function generateCitizenCode(): string
    {
        $lastUser = User::whereNotNull('code')->orderBy('code', 'desc')->first();

        if (isset($lastUser)) {
            $lastUserCode = $lastUser->code;
            $codeNum = explode('-', $lastUserCode)[1];
            $codeNum += 1;
            return 'hm-' . $codeNum;
        }

        return 'hm-2000000';
    }
}
