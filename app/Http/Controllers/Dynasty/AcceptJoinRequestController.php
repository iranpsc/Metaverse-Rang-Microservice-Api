<?php

namespace App\Http\Controllers\Dynasty;

use App\Constants\FamilyMembersType;
use App\Constants\JoinRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dynasty\RecievedJoinRequest;
use App\Models\Dynasty\DynastyMessage;
use App\Models\Dynasty\DynastyPrize;
use App\Models\Dynasty\JoinRequest;
use App\Models\DynastyPermission;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\GetOtpNotification;
use App\Notifications\JoinDynastyNotification;
use Illuminate\Support\Facades\Hash;
use Morilog\Jalali\Jalalian;

class AcceptJoinRequestController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $recievedJoinRequests = $user->recievedJoinRequests;
        if (count($recievedJoinRequests) == 0) {
            return response()->json(['message' => 'درخواستی ثبت نشده است!'], 200);
        }
        return RecievedJoinRequest::collection($recievedJoinRequests);
    }

    public function show(User $user, JoinRequest $recievedJoinRequest)
    {
        return new RecievedJoinRequest($recievedJoinRequest);
    }

    public function accept(User $user, JoinRequest $recievedJoinRequest)
    {
        if ($user->can('accept', $recievedJoinRequest)) {
            $code = random_int(100000, 999999);
            $recievedJoinRequest->otp()->create([
                'user_id' => $user->id,
                'code' => Hash::make($code)
            ]);
            $user->notify(new GetOtpNotification($code));
            return response()->json(['message' => 'کد تاییدی به شماره تلفن همراه شما ارسال گردید. جهت ادامه آنرا وارد کنید']);
        }
        return response()->json(['error' => 'خطایی رخ داده است.']);
    }

    public function verify(User $user, JoinRequest $recievedJoinRequest, Request $request)
    {
        $this->validate(
            $request,
            ['code' => 'required|numeric|min:6'],
            ['code.required' => 'کد تایید را وارد کنید']
        );
        $this->authorize('accept', $recievedJoinRequest);
        if (Hash::check($request->code, $recievedJoinRequest->otp->code)) {
            $requestedUser = $recievedJoinRequest->fromUser;
            $recievedJoinRequest->update(['status' => JoinRequestStatus::ACCEPTED]);

            if (isUnderEighteen($requestedUser) && $recievedJoinRequest->relationship == FamilyMembersType::FATHER) {
                $permssions = DynastyPermission::first();
                $$requestedUser->permissions()->create([
                    'BFR' => $permssions->BFR,
                    'SF' => $permssions->SF,
                    'W' => $permssions->W,
                    'JU' => $permssions->JU,
                    'DM' => $permssions->DM,
                    'PIUP' => $permssions->PIUP,
                    'PITC' => $permssions->PITC,
                    'PIC' => $permssions->PIC,
                    'ESOO' => $permssions->ESOO,
                    'COTB' => $permssions->COTB
                ]);
            } elseif(isUnderEighteen($user) && $recievedJoinRequest->relationship == FamilyMembersType::OFFSPRING) {
                $user->permissions->update(['verified'=>1]);
            }

            $dynasty = $requestedUser->dynasty;
            $family = $dynasty->family;
            $family->familyMembers()->create([
                'relationship' => $recievedJoinRequest->relationship,
                'user_id' => $user->id,
            ]);
            $requesterMessage = DynastyMessage::firstWhere('type', 'requester_accept_message')->message;
            $recieverMessage = DynastyMessage::firstWhere('type', 'reciever_accept_message')->message;

            $requesterMessage = str_replace(
                ['[sender-code]', '[reciever-code]', '[relationship]', '[created_at]', '[relationship]'],
                [
                    $requestedUser->code,
                    $user->code,
                    FamilyMembersType::familyMembersTypeList()[$recievedJoinRequest->relationship],
                    Jalalian::forge($recievedJoinRequest->created_at)->format('Y/m/d'),
                    FamilyMembersType::familyMembersTypeList()[$recievedJoinRequest->relationship]
                ],
                $requesterMessage
            );
            $recieverMessage = str_replace(
                ['[reciever-code]', '[created_at]', '[sender-code]', '[relationship]', '[sender-name]'],
                [
                    $user->code,
                    Jalalian::forge($recievedJoinRequest->created_at)->format('Y/m/d'),
                    $requestedUser->code,
                    FamilyMembersType::familyMembersTypeList()[$recievedJoinRequest->relationship],
                    $requestedUser->name,
                ],
                $recieverMessage
            );
            $prize = DynastyPrize::where('member', $recievedJoinRequest->relationship)->first();
            $requestedUser->recievedDynastyPrizes()->create([
                'prize_id' => $prize->id,
                'message' => $requesterMessage,
            ]);
            $requestedUser->notify(new JoinDynastyNotification([
                'type' => 'requester_accept_message',
                'title' => 'پیام تایید پیوستن به سلسله توسط کاربر مورد نظر',
                'message' => $requesterMessage,
            ]));

            $user->notify(new JoinDynastyNotification([
                'type' => 'reciever_accept_message',
                'title' => 'پیام تایید پیوستن به سلسه',
                'message' => $recieverMessage
            ]));

            $recievedJoinRequest->otp->delete();

            return response()->json(['success' => 'درخواست با موفقیت پذیرفته شد!'], 200);
        }
        return response()->json(['error' => 'کد تایید صحیح نمی باشد یا منقضی شده است!'], 200);
    }

    public function resendOtp(User $user, JoinRequest $recievedJoinRequest)
    {
        $code = random_int(100000, 999999);
        $recievedJoinRequest->otp()->updateOrCreate(
            ['user_id' => $user->id],
            ['code' => Hash::make($code)]
        );
        $user->notify(new GetOtpNotification($code));
        return response()->json(['success' => 'کد تایید مجددا ارسال گردید.'], 200);
    }

    public function reject(User $user, JoinRequest $recievedJoinRequest)
    {
        if($user->can('reject', $recievedJoinRequest)) {
            $recievedJoinRequest->update(['status' => 3]);
            $requestedUser = $recievedJoinRequest->fromUser;
            $requestedUser->notify(new JoinDynastyNotification([
                'type' => 'requester_reject_message',
                'title' => "رد درخواست پیوستن به سلسله",
                'message' => "درخواست پیوستن به سلسله شما توسط کاربر {$recievedJoinRequest->toUser->code} رد شد!",
            ]));
            return response()->json(['message' => 'درخواست پیوستن به سلسله رد شد!'], 200);
        }
        return response()->json(['error' => 'خطایی رخ داده است.']);
    }
}
