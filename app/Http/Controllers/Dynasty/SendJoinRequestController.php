<?php

namespace App\Http\Controllers\Dynasty;

use App\Constants\JoinRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddFamilyMemberRequest;
use App\Models\Dynasty\JoinRequest;
use App\Models\User;
use App\Notifications\GetOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class SendJoinRequestController extends Controller
{
    public function store(AddFamilyMemberRequest $request)
    {
        $user = $request->user();
        $user_to_add = User::findOrFail($request->user_id);
        if($user->can('addFamilyMember', $user_to_add, $request->relationship))
        {
            $joinRequest = JoinRequest::create([
                'from_user' => $user->id,
                'to_user' => $user_to_add->id,
                'status' => JoinRequestStatus::PENDING,
                'relation' => $request->relationship,
            ]);
            $code = Hash::make(random_int(100000, 999999));
            $joinRequest->otp()->create([
                'user_id' => $user->id,
                'code' => $code
            ]);
            $user->notify(new GetOtpNotification($code));
            return response()->json([
                'message' => 'کد تاییدی به شماره تلفن همراه شما ارسال گردید.',
                'id' => $joinRequest->id,
                'from_user' => $joinRequest->from_user,
                'to_user' => $joinRequest->to_user,
                'status' => $joinRequest->status,
                'relationship' => $joinRequest->relation
            ], 200);
        }
        abort(401, 'عملیات با خطا مواجه شد!');
    }

    public function verify(User $user, JoinRequest $joinRequest)
    {

    }
}
