<?php

namespace App\Http\Controllers;

use App\Constants\ChatSeenStatus;
use App\Http\Resources\Chat\ChatResource;
use App\Http\Resources\Chat\MessageResource;
use App\Models\Chat\Chat;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ChatController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        return ChatResource::collection(auth()->user()->chats);
    }

    /**
     * @param User $user
     * @param Request $request
     * @return ChatResource
     */
    public function newChat(User $user, Request $request): ChatResource
    {
        $chat = Chat::where('from_user', auth()->user()->id)->where('to_user', $user->id)->first();

        if ($chat) {
            return ChatResource::make($chat);
        }
        $chat = Chat::create([
            'from_user' => auth()->user()->id,
            'to_user' => $user->id
        ]);

        return ChatResource::make($chat);
    }

    /**
     * @param Chat $chat
     * @return JsonResponse
     */
    public function chat(Chat $chat): JsonResponse
    {
        if (auth()->user()->id == $chat->from_user || auth()->user()->id == $chat->to_user) {
            return response()->json([
                'chat' => $chat,
                'messages' => $chat->messages
            ]);
        }
        return response()->json([
            'message' => 'هیچ چتی یافت نشد'
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * @param Chat $chat
     * @param Request $request
     * @return JsonResponse|MessageResource
     */
    public function send(Chat $chat, Request $request)
    {

        if (auth()->user()->id == $chat->from_user || auth()->user()->id == $chat->to_user) {

            if (!empty($request->message) && $request->has('attachment')) {
                return \response()->json(['error', 'همزمان نمیتوان فایل و پیام ارسال کرد']);
            }
            if ($request->has('attachment')) {
                $request->validate([
                    'attachment' => 'mimes:pdf,jpg,jpeg,png'
                ]);
                $fileMMessage = $chat->messages()->create([
                    'user_id' => auth()->user()->id,
                    'message' => $request->file('attachment')->getClientOriginalName(),
                    'type' => $request->type,
                    'seen_status' => ChatSeenStatus::SENT
                ]);
                return MessageResource::make($fileMMessage);
            }
            $textMessage = $chat->messages()->create([
                'user_id' => auth()->user()->id,
                'message' => $request->get('message'),
                'type' => $request->type,
                'seen_status' => ChatSeenStatus::SENT,
            ]);
            return MessageResource::make($textMessage);
        }
        return response()->json([
            'message' => 'هیچ چتی یافت نشد'
        ], Response::HTTP_NOT_FOUND);

    }
}
