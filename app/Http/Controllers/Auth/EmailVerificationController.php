<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = User::find($request->route('id'));
        $user->update(['ip' => $request->ip()]);

        if ($user->hasVerifiedEmail()) {
            return redirect()->to('https://rgb.irpsc.com/metaverse/email?status=already_verified');
        } else if (!$request->hasValidSignature()) {
            return redirect()->to('https://rgb.irpsc.com/metaverse/email?status=invalid_link');
        } else {
            $user->markEmailAsVerified();
            return redirect()->to('https://rgb.irpsc.com/metaverse/email?status=verified');
        }
    }
}
