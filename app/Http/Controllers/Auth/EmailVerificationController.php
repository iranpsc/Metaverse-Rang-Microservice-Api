<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = User::find($request->route('id'));

        if ($user->hasVerifiedEmail()) {
            return response()->json(['error' => 'آدرس ایمیل قبلا تایید شده است']);
        }

        $user->markEmailAsVerified();

        return redirect()->to('https://rgb.irpsc.com/email/verified/true');
    }
}
