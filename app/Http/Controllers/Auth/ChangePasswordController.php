<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    public function __invoke(ChangePasswordRequest $request): \Illuminate\Http\JsonResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->password)
        ]);
        return response()->json([
            'success' => 'رمز عبور تغییر داده شد'
        ]);
    }
}
