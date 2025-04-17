<?php

namespace App\Http\Controllers\Api\V1\Dynasty;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dynasty\RecievedJoinRequest;
use App\Models\Dynasty\DynastyMessage;
use App\Models\Dynasty\DynastyPrize;
use App\Models\Dynasty\JoinRequest;
use App\Models\DynastyPermission;
use Illuminate\Http\Request;
use App\Notifications\JoinDynastyNotification;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;

class AcceptJoinRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('account.security')->only(['accept', 'reject']);
    }

    /**
     * Get all recieved join requests
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $requests = Auth::user()->receivedJoinRequests()
            ->with('fromUser')
            ->where('status', 0)
            ->latest()
            ->simplePaginate(10);
        return RecievedJoinRequest::collection($requests);
    }

    /**
     * Get a join request
     * @param JoinRequest $joinRequest
     * @return RecievedJoinRequest
     */
    public function show(JoinRequest $joinRequest)
    {
        $joinRequest->load('fromUser');
        $this->authorize('view', $joinRequest);
        return new RecievedJoinRequest($joinRequest);
    }

    /**
     * Accept a join request
     * @param Request $request
     * @param JoinRequest $joinRequest
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function accept(Request $request, JoinRequest $joinRequest)
    {
        // Check if the user is authorized to accept the join request
        $this->authorize('accept', $joinRequest);

        $requestedUser = $joinRequest->fromUser;
        $joinRequest->update(['status' => 1]);
        $user = $request->user();

        // If the requested user is under 18 and the relationship is father, give the user dynasty permissions
        if ($requestedUser->isUnderEighteen() && $joinRequest->relationship === 'father') {
            $permssions = DynastyPermission::first();
            $$requestedUser->permissions()->create([
                'verified' => 1,
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
        } elseif ($user->isUnderEighteen() && $joinRequest->relationship === 'offspring') {
            // If the user is under 18 and the relationship is offspring, give the requested user dynasty permissions
            $user->permissions->update(['verified' => 1]);
        }

        $dynasty = $requestedUser->dynasty;
        $family = $dynasty->family;

        // Create a family member for the requested user
        $family->familyMembers()->create([
            'relationship' => $joinRequest->relationship,
            'user_id' => $user->id,
        ]);

        $requesterMessage = DynastyMessage::firstWhere('type', 'requester_accept_message')->message;
        $recieverMessage = DynastyMessage::firstWhere('type', 'reciever_accept_message')->message;

        // Replace the placeholders in the messages with the actual values
        $requesterMessage = str_replace(
            ['[sender-code]', '[reciever-code]', '[relationship]', '[created_at]', '[relationship]'],
            [
                $requestedUser->code,
                $user->code,
                $joinRequest->getRelationShipTitle(),
                Jalalian::forge($joinRequest->created_at)->format('Y/m/d'),
                $joinRequest->getRelationShipTitle()
            ],
            $requesterMessage
        );

        // Replace the placeholders in the messages with the actual values
        $recieverMessage = str_replace(
            ['[reciever-code]', '[created_at]', '[sender-code]', '[relationship]', '[sender-name]'],
            [
                $user->code,
                Jalalian::forge($joinRequest->created_at)->format('Y/m/d'),
                $requestedUser->code,
                $joinRequest->getRelationShipTitle(),
                $requestedUser->name,
            ],
            $recieverMessage
        );

        // Get the prize for the requested user
        $prize = DynastyPrize::where('member', $joinRequest->relationship)->first();

        // Create a recieved dynasty prize for the requested user
        $requestedUser->recievedDynastyPrizes()->create([
            'prize_id' => $prize->id,
            'message' => $requesterMessage,
        ]);

        // Notify the requested user and the user about the join request
        $requestedUser->notify(new JoinDynastyNotification([
            'type' => 'requester_accept_message',
            'message' => $requesterMessage,
            'request' => $joinRequest
        ]));

        // Notify the user about the join request
        $user->notify(new JoinDynastyNotification([
            'type' => 'reciever_accept_message',
            'message' => $recieverMessage,
            'request' => $joinRequest
        ]));

        // Return the join request
        return new RecievedJoinRequest($joinRequest->refresh());
    }

    /**
     * Reject a join request
     * @param Request $request
     * @param JoinRequest $joinRequest
     * @return RecievedJoinRequest
     */
    public function reject(Request $request, JoinRequest $joinRequest)
    {
        // Check if the user is authorized to reject the join request
        $this->authorize('reject', $joinRequest);

        // Update the join request status
        $joinRequest->update(['status' => -1]);

        $requestedUser = $joinRequest->fromUser;

        // Notify the requested user and the user about the join request
        $requestedUser->notify(new JoinDynastyNotification([
            'type' => 'reciever_reject_message',
            'request' => $joinRequest,
            'message' => "درخواست پیوستن به سلسله از طرف {$requestedUser->code} توسط شما رد شد.",
        ]));

        // Notify the user about the join request
        $request->user()->notify(new JoinDynastyNotification([
            'type' => 'requester_reject_message',
            'request' => $joinRequest,
            'message' => "درخواست پیوستن به سلسله شما توسط کاربر {$joinRequest->toUser->code} رد شد!",
        ]));

        // Return the join request
        return new RecievedJoinRequest($joinRequest->refresh());
    }
}
