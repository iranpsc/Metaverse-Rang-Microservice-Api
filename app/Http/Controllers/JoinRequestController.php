<?php

namespace App\Http\Controllers;

use App\Constants\JoinRequestStatus;
use App\Events\JoinDynastyRequestSent;
use App\Http\Requests\SendJoinRequest;
use App\Mail\DynastyJoinRequestSent as MailDynastyJoinRequestSent;
use App\Mail\DynastyJoinRequestSentMail;
use App\Mail\FamilyMemeberAcceptedJoinRequest;
use App\Models\Dynasty\Dynasty;
use App\Models\Dynasty\DynastyMessage;
use App\Models\Dynasty\FamilyMember;
use App\Models\Dynasty\JoinRequest;
use App\Models\Dynasty\DynastyPrize;
use App\Models\Level\Prize;
use App\Models\Otp;
use App\Models\User;
use App\Notifications\AcceptDynastyJoinRequest;
use App\Notifications\DynastyJoinRequestSent;
use App\Notifications\FamilyMemberAcceptedJoinRequest;
use App\Notifications\JoinDynastyRequestNotification;
use App\Notifications\UserInvitedToDynasty;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class JoinRequestController extends Controller
{

    /**
     * @param SendJoinRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function store(SendJoinRequest $request): JsonResponse
    {
        $joinRequest = JoinRequest::where('from_user', $request->user()->id)->where('to_user', $request->to_user)->latest()->first();

        if (!empty($joinRequest)) {
            if ($joinRequest->updated_at->addMonths(1) > now()) {
                return \response()->json([
                    'message' => 'محدودیت زمانی ارسال درخواست ملحق شدن به سلسله برای کاربر مورد نظر از سمت شما به حداکثر خود رسیده است',
                ], Response::HTTP_METHOD_NOT_ALLOWED);
            }
        }

        $toUser = User::findOrFail($request->getToUser());

        if ($request->user()->can('addFamilyMember', [$toUser, $request->relation])) {
            /*if (isUnderEighteen($request->user())) {
                if ($request->has('no_father')) {
                    if (!$request->has('mother_code') || !$request->has('death_license')) {
                        return \response()->json([
                            'message' => 'لطفا گواهی فوت بدر و کد کاربری مادر را وارد نمایید'
                        ], Response::HTTP_BAD_REQUEST);
                    }
                    $file = $request->file('death_license');
                    $path = env('FTP_ENDPOINT') . $file->store('dynasty/docs');
                    $joinRequest = JoinRequest::create([
                        'relation' => $request->getRelation(),
                        'to_user' => $request->getToUser(),
                        'from_user' => auth()->user()->id,
                        'no_father' => $request->getNoFather(),
                        'death_license' => $path,
                        'mother_code' => $request->getMotherCode(),
                    ]);

                    if (Otp::where('user_id', auth()->user()->id)->exists()) {
                        if (auth()->user()->otp->created_at->addMinutes(2) < now()) {
                            return \response()->json([
                                'message' => 'کد تایید قبلا برای شما ارسال شده است لطقا بعد از ۲ دقیقه مجدد تلاش کنید'
                            ], Response::HTTP_BAD_REQUEST);
                        } else {
                            $this->sendOtp($joinRequest, auth()->user()->id);
                            return \response()->json([
                                'message' => 'بیامی حاوی کد تایید برای شما ارسا شد'
                            ], Response::HTTP_OK);

                        }
                    } else {
                        $this->sendOtp($joinRequest, auth()->user()->id);
                        return \response()->json([
                            'message' => 'بیامی حاوی کد تایید برای شما ارسا شد'
                        ], Response::HTTP_OK);
                    }
                } else {
                    $joinRequest = JoinRequest::create([
                        'to_user' => $request->getToUser(),
                        'relation' => $request->getRelation(),
                        'from_user' => $request->user()->id,
                    ]);
                    $this->sendOtp($joinRequest, auth()->user()->id);
                    return \response()->json([
                        'message' => 'بیامی حاوی کد تایید برای شما ارسال شد'
                    ], Response::HTTP_OK);
                }
            } else {*/
            $joinRequest = JoinRequest::create([
                'to_user' => $request->getToUser(),
                'from_user' => auth()->user()->id,
                'relation' => $request->getRelation()
            ]);
            $this->sendOtp($joinRequest, $request->user()->id);
            return \response()->json([
                'message' => 'بیامی حاوی کد تایید برای شما ارسال شد'
            ], Response::HTTP_OK);
            /*}*/
        }
        return \response()->json([
            'message' => 'عملیات نا معتبر'
        ], Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * @param $joinRequest
     * @param $userCode
     * @return void
     * @throws \Exception
     */
    public function sendOtp($joinRequest, $userCode): void
    {
        $verifyCode = random_int(100000, 999999);
        Otp::updateOrCreate([
            'user_id' => $userCode
        ], [
            'code' => $verifyCode,
            'user_id' => $userCode,
            'otp_reason' => 'joinDynastyRequest'
        ]);
        JoinDynastyRequestSent::dispatch($joinRequest, $verifyCode);
    }


    /**
     * @return JsonResponse
     * @throws \Exception
     */
    public function resendOtp(Request $request): JsonResponse
    {
        if (Otp::where('user_id', auth()->user()->id)->exists()) {
            if ($request->user()->otp->created_at->addMinutes(5) > now()) {
                return \response()->json([
                    'message' => 'کد تایید قبلا برای شما ارسال شده است لطفا پس از ۵ دقیقه مجدد تلاش کنید'
                ], Response::HTTP_FORBIDDEN);
            } else {
                $verifyCode = random_int(100000, 999999);
                $joinRequest = JoinRequest::where('from_user', $request->user()->id)->orderBy('created_at', 'DESC')->latest('from_user')->first();
                $request->user()->otp()->updateOrCreate([
                    'user_id' => $request->user()->id
                ], [
                    'code' => $verifyCode,
                    'user_id' => $request->user()->id,
                    'otp_reason' => 'joinDynastyRequest'
                ]);
                JoinDynastyRequestSent::dispatch($joinRequest, $verifyCode);
                return \response()->json([
                    'message' => 'کد تایید مجدد برای شما ارسال شد'
                ], Response::HTTP_OK);
            }
        }
        $verifyCode = random_int(100000, 999999);
        $joinRequest = JoinRequest::where('from_user', $request->user()->id)->orderBy('created_at', 'DESC')->latest('from_user')->first();
        $request->user()->otp()->updateOrCreate([
            'user_id' => $request->user()->id
        ], [
            'code' => $verifyCode,
            'user_id' => $request->user()->id,
            'otp_reason' => 'joinDynastyRequest'
        ]);
        JoinDynastyRequestSent::dispatch($joinRequest, $verifyCode);
        return \response()->json([
            'message' => 'کد تایید مجدد برای شما ارسال شد'
        ], Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $otp = $request->user()->otp->where('otp_reason', 'joinDynastyRequest')->first();

        if ($request->get('code') != $otp->code || $otp->updated_at->addMinutes(5) < now()) {
            abort(401, 'کد تایید وارد شده صحیح نیست یا منقضی شده است');
        }

        $joinRequest = JoinRequest::where('from_user', $request->user()->id)->orderBy('created_at', 'DESC')->latest('from_user')->first();
        $sender = $request->user();
        $reciever = User::findOrFail($joinRequest->to_user);

        $joinRequest->update([
            'status' => JoinRequestStatus::WAITING
        ]);

        $senderMessage = DynastyMessage::where('type', 'requester')->first();
        $recieverMessage = DynastyMessage::where('type', 'reciever')->first();

        $senderMessage = str_replace(
            ['[relation]', '[sender-code]', '[date]', '[reciever-code]', '[relation]', '[relation]'],
            [
                getFamilyRelationship($joinRequest->relation),
                $sender->code,
                \Morilog\Jalali\Jalalian::forge($joinRequest->created_at)->format('Y-m-d'),
                $reciever->code,
                getFamilyRelationship($joinRequest->relation),
                getFamilyRelationship($joinRequest->relation),
            ],
            $senderMessage->message
        );
        $recieverMessage = str_replace(
            ['[reciever]', '[sender]', '[relation]', '[sender]', '[accept-link]'],
            [
                $reciever->name,
                $sender->name,
                getFamilyRelationship($joinRequest->relation),
                $sender->name,
                "<a href='https://rgb.irpsc.com/citizen/'.$sender->code.'>می بذیرم</a>"
            ],
            $recieverMessage->message
        );

        Mail::to($sender)->send(new DynastyJoinRequestSentMail($senderMessage));

        $reciever->notify(new UserInvitedToDynasty($recieverMessage));


        $otp->delete();

        return \response()->json([
            'message' => 'درخواست شما با موفقیت ارسال شد'
        ], Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function acceptRequest(Request $request, JoinRequest $joinRequest): JsonResponse
    {
        if($joinRequest->to_user !== $request->user()->id) {
            abort(403, 'این درخواست متعلق به شما نمی باشد');
        }

        $user = $request->user();
        $code = random_int(100000, 999999);
        Otp::updateOrCreate([
            'user_id' => $user->id
        ], [
            'user_id' => $user->id,
            'code' => $code,
            'otp_reason' => 'accept-dyansty-join-request'
        ]);
        $user->notify(new AcceptDynastyJoinRequest($joinRequest, $code));
        return \response()->json([
            'message' => 'پیامی حاوی کد تایید برای شما ارسال شد',
        ], Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyAcceptOtp(Request $request, JoinRequest $joinRequest): JsonResponse
    {
        if($joinRequest->to_user !== $request->user()->id) {
            abort(403, 'این درخواست متعلق به شما نمی باشد');
        }

        $otp = Otp::where('user_id', $request->user()->id)
        ->where('otp_reason', 'accept-dyansty-join-request')->first();

        // if($request->code != $otp->code || $otp->updated_at->addMinutes(5) < now()) {
        //     abort(403, 'کد تایید اشتباه است یا منقضی شده است');
        // }

        $toUser = User::findOrFail($joinRequest->to_user);
        $fromUser = User::findOrFail($joinRequest->from_user);

        $joinRequest->update([
            'status' => JoinRequestStatus::ACCEPTED
        ]);

        $this->addUserToFamilyMembers($fromUser, $joinRequest);
        $this->notifyToUser($fromUser, $joinRequest);
        $this->calculateProfit($joinRequest);
        // $toUser->otp->delete();
        return \response()->json([
            'message' => 'درخواست با موفقیت قبول شد و به سلسله منتقل شدید'
        ], Response::HTTP_OK);

        return \response()->json([
            'message' => 'کد وارد شده صحیح نیست'
        ], Response::HTTP_BAD_REQUEST);
    }
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function rejectJoinRequest(Request $request): JsonResponse
    {
        if (!$request->has('request_id')) {
            return \response()->json([
                'message' => 'کد درخواست را وارد کنید'
            ], Response::HTTP_BAD_REQUEST);
        }
        $joinRequest = JoinRequest::findOrFail($request->get('request_id'));
        if ($joinRequest->to_user == auth()->user()->id) {
            $joinRequest->update([
                'status' => JoinRequestStatus::REJECTED
            ]);
            return \response()->json([
                'message' => 'درخواست با موفقیت رد شد'
            ], Response::HTTP_OK);
        }
        return \response()->json([
            'message' => 'درخواست مورد نظر نا معتبر است'
        ], Response::HTTP_METHOD_NOT_ALLOWED);
    }


    /**
     * @param $fromUser
     * @param $joinRequest
     * @return void
     */
    public function addUserToFamilyMembers($fromUser, $joinRequest): void
    {
        $fromUser->dynasty->family->familyMembers()->create([
            'relationship' => $joinRequest->relation,
            'user_id' => $joinRequest->to_user
        ]);

        $prize = DynastyPrize::where('member',$joinRequest->relation)->first();

        $fromUser->assets->increment('psc',$prize->psc);
        $fromUser->assets->increment('satisfaction',$prize->satisfaction);

        $referalLimit = $fromUser->referalLimit;

        $referalLimit_amount = $referalLimit->limit_amount +
        ($referalLimit->limit_amount * $prize->introduction_profit_increase);

        $referalLimit->update([
            'limit_amount' => $referalLimit_amount
        ]);

    }

    /**
     * @param $fromUser
     * @param $joinRequest
     * @return void
     */
    public function notifyToUser($fromUser, $joinRequest): void
    {
        Mail::to($fromUser)->send(new FamilyMemeberAcceptedJoinRequest($joinRequest, $fromUser));
        $fromUser->notify(new FamilyMemberAcceptedJoinRequest($joinRequest, $fromUser));
    }

       /**
     * @param $joinRequest
     * @return void
     */
    private function calculateProfit($joinRequest): void
    {
        $user = auth()->user();
        $familyMemberProfit = DynastyPrize::where('member', $joinRequest->relation)->first();
        // soode moarefi member
        $introductionProfit = $user->variables->referral_profit * $familyMemberProfit->introduction_profit_increase;
        // zakhire ye soode sarmayeh anbashteh
        $accumulatedCapitalReserve = $user->variables->withdraw_profit * $familyMemberProfit->accumulated_capital_reserve;
        // data storage
        $dataStorage = $user->variables->data_storage * $familyMemberProfit->data_storage;

        // increment user variable
        $user->variables->increment('data_storage', $dataStorage);
        $user->variables->increment('withdraw_profit', $accumulatedCapitalReserve);
        $user->variables->increment('referral_profit', $introductionProfit);

        // increment user assets
        $user->assets->increment('psc',$familyMemberProfit->psc);
        $user->assets->increment('satisfaction',$familyMemberProfit->satisfaction);
    }
}
