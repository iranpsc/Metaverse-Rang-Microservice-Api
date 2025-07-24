<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use App\Http\Resources\VideoCommentResource;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;

class CommentReplyController extends Controller
{
    /**
     * Get replies for a specific comment.
     *
     * @param  \App\Models\Video  $video
     * @param  \App\Models\Comment  $comment
     * @return \Illuminate\Http\Response
     */
    public function index(Comment $comment)
    {
        $replies = $comment->replies()
            ->with(['user:id,name,code', 'user.latestProfilePhoto'])
            ->withCount(['likes', 'dislikes'])
            ->orderBy('created_at', 'asc')
            ->simplePaginate(10);

        return VideoCommentResource::collection($replies);
    }

    /**
     * Store a reply to a comment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Video  $video
     * @param  \App\Models\Comment  $comment
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Comment $comment)
    {
        $this->authorize('reply', $comment);

        $request->validate(['content' => 'required|string|max:2000']);

        // Ensure we're replying to a parent comment, not a reply
        $parentComment = $comment->isReply() ? $comment->parent : $comment;
        $video = $parentComment->commentable;

        $reply = $video->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->content,
            'parent_id' => $parentComment->id
        ]);

        $reply->load(['user:id,name,code', 'user.latestProfilePhoto']);

        return new VideoCommentResource($reply);
    }

    /**
     * Update a reply to a comment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Video  $video
     * @param  \App\Models\Comment  $comment
     * @param  \App\Models\Comment  $reply
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Comment $comment, Comment $reply)
    {
        $this->authorize('update', $reply);

        $request->validate(['content' => 'required|string|max:2000']);

        $reply->update([
            'user_id' => $request->user()->id,
            'content' => $request->content
        ]);

        return new VideoCommentResource($reply->refresh());
    }

    /**
     * Delete a reply to a comment.
     *
     * @param  \App\Models\Comment  $comment
     * @param  \App\Models\Comment  $reply
     * @return \Illuminate\Http\Response
     */
    public function destroy(Comment $comment, Comment $reply)
    {
        $this->authorize('update', $reply);

        $reply->delete();

        $reply->interactions()->delete();

        return new JsonResponse([], 200);
    }

    /**
     * Like or dislike a reply based on the 'liked' query parameter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Video  $video
     * @param  \App\Models\Comment  $comment
     * @param  \App\Models\Comment  $reply
     * @return \Illuminate\Http\Response
     */
    public function interactions(Request $request, Video $video, Comment $comment, Comment $reply)
    {
        $request->validate(['liked' => 'required|boolean']);

        $likedBool = (bool) $request->input('liked');

        $this->authorize($likedBool ? 'like' : 'dislike', $reply);

        $reply->interactions()->updateOrCreate(
            [
                'user_id' => $request->user()->id
            ],
            [
                'liked' => $likedBool,
                'ip_address' => $request->ip()
            ]
        );

        return new JsonResponse([], 200);
    }
}
