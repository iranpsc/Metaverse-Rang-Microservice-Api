<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function changePassword(ResetPasswordRequest $request)
    {
        $request->user()->update([
            'password' => Hash::make($request->password)
        ]);
        return response()->json([
            'success' => 'رمز عبور تغییر داده شد'
        ]);
    }
}
