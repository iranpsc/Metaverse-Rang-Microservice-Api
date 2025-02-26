<?php

namespace App\Http\Controllers\Api\V1\Dynasty;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddFamilyMemberRequest;
use App\Http\Resources\Dynasty\SentRequestsResource;
use App\Models\Dynasty\DynastyMessage;
use App\Models\Dynasty\JoinRequest;
use App\Models\DynastyPermission;
use App\Models\User;
use App\Notifications\JoinDynastyNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class SendJoinRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('account.security')->only(['store', 'destroy']);
    }

    /**
     * Return all sent join requests.
     */
    public function index(Request $request)
    {
        return SentRequestsResource::collection($request->user()->sentJoinRequests);
    }

    /**
     * Return a specific join request.
     */
    public function show(JoinRequest $joinRequest)
    {
        $this->authorize('view', $joinRequest);
        return new SentRequestsResource($joinRequest);
    }

    /**
     * Create and send a join request.
     */
    public function store(AddFamilyMemberRequest $request)
    {
        $user = $request->user();
        $userToAdd = User::findOrFail($request->user);
        $relationship = $request->relationship;

        $this->validateOffspringPermissions($userToAdd, $relationship, $request);
        $this->authorize('addFamilyMember', [$userToAdd, $relationship]);

        $date = Jalalian::forge(now())->format('Y/m/d');
        $messages = $this->prepareMessages($user, $userToAdd, $relationship, $date);

        $joinRequest = $this->createJoinRequest($user, $userToAdd, $relationship, $messages['receiver']);

        if ($this->shouldSetOffspringPermissions($relationship, $userToAdd)) {
            $this->setOffspringPermissions($joinRequest, $request->permissions);
        }

        $this->sendNotifications($user, $userToAdd, $joinRequest, $messages);

        return new SentRequestsResource($joinRequest);
    }

    private function validateOffspringPermissions(User $userToAdd, string $relationship, Request $request): void
    {
        if ($relationship === 'offspring' && !$userToAdd->isUnderEighteen() && $request->has('permissions')) {
            abort(403, 'شما مجاز به تعریف دسترسی برای فرزند بالای 18 سال نیستید.');
        }
    }

    private function prepareMessages(User $sender, User $receiver, string $relationship, string $date): array
    {
        $senderMessage = DynastyMessage::where('type', 'requester_confirmation_message')->value('message') ?? '';
        $receiverMessage = DynastyMessage::where('type', 'reciever_message')->value('message') ?? '';

        $replacements = [
            '[sender-code]' => $sender->code,
            '[reciever-code]' => $receiver->code,
            '[relationship]' => getRelationshipTitle($relationship),
            '[created_at]' => $date,
            '[sender-name]' => $sender->name,
            '[reciever-name]' => $receiver->name
        ];

        return [
            'sender' => str_replace(array_keys($replacements), array_values($replacements), $senderMessage),
            'receiver' => str_replace(array_keys($replacements), array_values($replacements), $receiverMessage)
        ];
    }

    private function createJoinRequest(User $from, User $to, string $relationship, string $message): JoinRequest
    {
        return JoinRequest::create([
            'from_user' => $from->id,
            'to_user' => $to->id,
            'status' => 0,
            'relationship' => $relationship,
            'message' => $message,
        ]);
    }

    private function shouldSetOffspringPermissions(string $relationship, User $user): bool
    {
        return $relationship === 'offspring' && $user->isUnderEighteen();
    }

    private function setOffspringPermissions(JoinRequest $joinRequest, array $permissions): void
    {
        $joinRequest->toUser->permissions()->create(array_merge(
            ['verified' => false],
            $permissions
        ));
    }

    private function sendNotifications(User $sender, User $receiver, JoinRequest $joinRequest, array $messages): void
    {
        $sender->notify(new JoinDynastyNotification([
            'type' => 'requester_confirmation_message',
            'request' => $joinRequest,
            'message' => $messages['sender']
        ]));

        $receiver->notify(new JoinDynastyNotification([
            'type' => 'reciever_message',
            'request' => $joinRequest,
            'message' => $messages['receiver']
        ]));
    }

    /**
     * Remove a join request.
     */
    public function destrory(JoinRequest $joinRequest)
    {
        $this->authorize('delete', $joinRequest);
        $joinRequest->delete();
        return response()->noContent(200);
    }

    /**
     * Retrieve default permissions.
     */
    public function getPermissions(Request $request)
    {
        $request->validate(['relationship' => 'required|string|in:offspring']);
        $permissions = DynastyPermission::first();
        return response()->json(['permissions' => $permissions]);
    }

    /**
     * Search for a user.
     */
    public function search(Request $request)
    {
        $request->validate(['searchTerm' => 'required|string']);
        $searchTerm = '%' . $request->searchTerm . '%';

        $users = User::select(['id', 'code', 'name'])
            ->where('name', 'like', $searchTerm)
            ->orWhere('code', 'like', $searchTerm)
            ->orWhereHas('kyc', function ($query) use ($searchTerm) {
                $query->where('fname', 'like', $searchTerm)
                      ->orWhere('lname', 'like', $searchTerm);
            })
            ->with(['kyc:id,user_id,fname,lname,birthdate,status', 'latestProfilePhoto'])
            ->get();

        return response()->json($users->map(function ($user) {
            return [
                'id'       => $user->id,
                'code'     => $user->code,
                'name'     => $user->verified() ? $user->kyc->full_name : $user->name,
                'image'    => $user->latestProfilePhoto?->url,
                'verified' => $user->verified(),
                'age'      => $user->verified() ? $user->kyc->birthdate->diffInYears(now()) : null,
            ];
        }));
    }
}
