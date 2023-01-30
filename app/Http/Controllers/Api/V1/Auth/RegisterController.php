<?php

namespace App\Http\Controllers\Api\V1\Auth;

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
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        if ($request->has('referral')) {
            $reference_user = User::firstWhere('code', $request->referral);
            Referal::create([
                'reference_id' => $reference_user->id,
                'referer_id' => $user->id,
            ]);
        }

        $user->registered();

        event(new Registered($user));

        return response()->noContent(200);
    }
}
