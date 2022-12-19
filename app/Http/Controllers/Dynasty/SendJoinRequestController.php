<?php

namespace App\Http\Controllers\Dynasty;

use App\Constants\FamilyMembersType;
use App\Constants\JoinRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddFamilyMemberRequest;
use App\Http\Resources\Dynasty\SentRequestsResource;
use App\Models\Dynasty\DynastyMessage;
use App\Models\Dynasty\JoinRequest;
use App\Models\DynastyPermission;
use App\Models\User;
use App\Notifications\GetOtpNotification;
use App\Notifications\JoinDynastyNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class SendJoinRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $sentJoinRequests = $user->sentJoinRequests;
        if (!$sentJoinRequests) {
            return response()->json(['message' => 'درخواستی ثبت نشده است!'], 200);
        }

        return SentRequestsResource::collection($sentJoinRequests);
    }

    public function show(User $user, JoinRequest $sentJoinRequest)
    {
        return new SentRequestsResource($sentJoinRequest);
    }

    public function getPermissions(Request $request)
    {
        if ($request->has('relationship') && $request->relationship === 'offspring') {
            $permissions = DynastyPermission::first();
            return response()->json(['permissions' => $permissions], 200);
        } else {
            return response()->json(['error' => 'درخواست نا معتبر است.'], 404);
        }
    }

    public function store(AddFamilyMemberRequest $request)
    {
        $user = $request->user();
        $user_to_add = User::findOrFail($request->user_id);
        if ($user->can('addFamilyMember', [$user_to_add, $request->relationship])) {
            $joinRequest = JoinRequest::create([
                'from_user' => $user->id,
                'to_user' => $user_to_add->id,
                'status' => JoinRequestStatus::PENDING,
                'relationship' => $request->relationship,
            ]);
            if ($request->relationship === 'offspring' && isUnderEighteen($joinRequest->toUser)) {
                $permissions = $request->permissions;
                $joinRequest->toUser->permissions()->create([
                    'verified' => false,
                    'BFR'      => $permissions['BFR'],
                    'SF'       => $permissions['SF'],
                    'W'        => $permissions['W'],
                    'JU'       => $permissions['JU'],
                    'DM'       => $permissions['DM'],
                    'PIUP'     => $permissions['PIUP'],
                    'PITC'     => $permissions['PITC'],
                    'PIC'      => $permissions['PIC'],
                    'ESOO'     => $permissions['ESOO'],
                    'COTB'     => $permissions['COTB']
                ]);
            }

            $code = random_int(100000, 999999);
            $joinRequest->otp()->create([
                'user_id' => $user->id,
                'code' => Hash::make($code)
            ]);
            $user->notify(new GetOtpNotification($code));
            return response()->json([
                'message' => 'کد تاییدی به شماره تلفن همراه شما ارسال گردید.',
                'id' => $joinRequest->id,
                'from_user' => $joinRequest->from_user,
                'to_user' => $joinRequest->to_user,
                'status' => $joinRequest->status,
                'relationship' => $joinRequest->relationship
            ], 200);
        }
        return response()->json(['error' => 'عملیات با خطا مواجه شد!'], 200);
    }

    public function verify(User $user, JoinRequest $sentJoinRequest, Request $request)
    {
        $this->validate(
            $request,
            ['code' => 'required|numeric|min:6'],
            ['code.required' => 'کد تایید را وارد کنید']
        );
        $otp = $sentJoinRequest->otp->where('user_id', $user->id)->first();
        if (Hash::check($request->code, $otp->code)) {
            $sentJoinRequest->update(['status' => 1]);
            $senderConfirmationMessage = DynastyMessage::where('type', 'requester_confirmation_message')->first();
            $senderConfirmationMessage = $senderConfirmationMessage->message;
            $recieverMessage = DynastyMessage::where('type', 'reciever_message')->first();
            $recieverMessage = $recieverMessage->message;

            $senderConfirmationMessage = str_replace(
                [
                    '[sender-code]',
                    '[relationship]',
                    '[reciever-code]',
                    '[created_at]',
                    '[sender-name]',
                    '[reciever-name]',
                ],
                [
                    $request->user()->code,
                    FamilyMembersType::familyMembersTypeList()[$sentJoinRequest->relationship],
                    $sentJoinRequest->toUser->code,
                    Jalalian::forge($sentJoinRequest->created_at)->format('Y/m/d'),
                    $sentJoinRequest->fromUser->name,
                    $sentJoinRequest->toUser->name,
                ],
                $senderConfirmationMessage
            );
            $recieverMessage = str_replace(
                [
                    '[reciever-code]',
                    '[sender-code]',
                    '[relationship]',
                    '[relationship]',
                    '[sender-code]',
                    '[created_at]',
                    '[sender-name]',
                    '[reciever-name]',
                ],
                [
                    $sentJoinRequest->toUser->code,
                    $sentJoinRequest->fromUser->code,
                    FamilyMembersType::familyMembersTypeList()[$sentJoinRequest->relationship],
                    FamilyMembersType::familyMembersTypeList()[$sentJoinRequest->relationship],
                    $sentJoinRequest->fromUser->code,
                    Jalalian::forge($sentJoinRequest->created_at)->format('Y/m/d'),
                    $sentJoinRequest->fromUser->name,
                    $sentJoinRequest->toUser->name,
                ],
                $recieverMessage
            );
            $user->notify(new JoinDynastyNotification([
                'type' => 'requester_confirmation_message',
                'title' => 'پیام تایید ارسال درخواست پیوستن به سلسله',
                'request' => $sentJoinRequest,
                'message' => $senderConfirmationMessage
            ]));
            $sentJoinRequest->toUser->notify(new JoinDynastyNotification([
                'type' => 'reciever_message',
                'title' => 'پیام دریافتی درخواست پیوستن به سلسله',
                'request' => $sentJoinRequest,
                'message' => $recieverMessage
            ]));

            $sentJoinRequest->update(['message' => $recieverMessage]);
            $sentJoinRequest->otp->delete();
            return response()->json(['success' => 'درخواست پیوستن به سلسله با موفقیت ارسال گردید.'], 200);
        }
        return response()->json(['error' => 'کد تایید صحیح نمی باشد یا منقضی شده است!'], 404);
    }

    public function resendOtp(User $user, JoinRequest $sentJoinRequest)
    {
        $code = random_int(100000, 999999);
        $sentJoinRequest->otp()->updateOrCreate(
            ['user_id' => $user->id],
            ['code' => Hash::make($code)]
        );
        $user->notify(new GetOtpNotification($code));
        return response()->json(['success' => 'کد تایید مجددا ارسال گردید.'], 200);
    }

    public function cancel(User $user, JoinRequest $sentJoinRequest)
    {
        $sentJoinRequest->update(['status' => JoinRequestStatus::CANCELED]);
    }
}
