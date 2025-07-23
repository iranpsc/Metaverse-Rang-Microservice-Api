<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use App\Http\Resources\VideoCommentResource;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;

class VideoCommentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Video $video)
    {
        return response()->json([
            'success' => 'This is a success response'
        ]);

        // $comments = $video->comments()
        //     ->whereNull('parent_id') // Only get parent comments
        //     ->with([
        //         'user:id,name,code',
        //         'user.latestProfilePhoto',
        //         'replies' => function ($query) {
        //             $query->with(['user:id,name,code', 'user.latestProfilePhoto'])->orderBy('created_at', 'asc');
        //         }
        //     ])
        //     ->orderByDesc('likes_count')
        //     ->simplePaginate(10);
        // return VideoCommentResource::collection($comments);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Video $video)
    {
        $request->validate(['content' => 'required|string|max:2000']);
        $comment = $video->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->content
        ]);
        return (new VideoCommentResource($comment))->response()->setStatusCode(201);
    }

    /**
     * Store a reply to a comment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Video  $video
     * @param  \App\Models\Comment  $comment
     * @return \Illuminate\Http\Response
     */
    public function storeReply(Request $request, Video $video, Comment $comment)
    {
        $this->authorize('reply', $comment);
        $request->validate(['content' => 'required|string|max:2000']);

        // Ensure we're replying to a parent comment, not a reply
        $parentComment = $comment->isReply() ? $comment->parent : $comment;

        $reply = $video->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->content,
            'parent_id' => $parentComment->id
        ]);

        $reply->load(['user:id,name,code', 'user.latestProfilePhoto']);

        return (new VideoCommentResource($reply))->response()->setStatusCode(201);
    }

    /**
     * Get replies for a specific comment.
     *
     * @param  \App\Models\Video  $video
     * @param  \App\Models\Comment  $comment
     * @return \Illuminate\Http\Response
     */
    public function getReplies(Video $video, Comment $comment)
    {
        $replies = $comment->replies()
            ->with(['user:id,name,code', 'user.latestProfilePhoto'])
            ->orderBy('created_at', 'asc')
            ->simplePaginate(10);

        return VideoCommentResource::collection($replies);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Video  $video
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Video $video, Comment $comment)
    {
        $this->authorize('update', $comment);
        $request->validate(['content' => 'required|string|max:2000']);
        $comment->update([
            'user_id' => $request->user()->id,
            'content' => $request->content
        ]);
        return new VideoCommentResource($comment->refresh());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Video  $video
     * @return \Illuminate\Http\Response
     */
    public function destroy(Video $video, Comment $comment)
    {
        $this->authorize('update', $comment);
        $comment->delete();
        $comment->interactions()->delete();
        return new JsonResponse([], 200);
    }

    /**
     * Like the comment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function like(Request $request, Video $video, Comment $comment)
    {
        $this->authorize('like', $comment);
        $comment->interactions()->updateOrCreate(
            [
                'user_id' => $request->user()->id
            ],
            [
                'liked' => true,
                'ip_address' => $request->ip()
            ]
        );
        return new JsonResponse([], 200);
    }

    /**
     * Dislike the comment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Video  $video
     * @return \Illuminate\Http\Response
     */
    public function dislike(Request $request, Video $video, Comment $comment)
    {
        $this->authorize('dislike', $comment);
        $comment->interactions()->updateOrCreate(
            [
                'user_id' => $request->user()->id
            ],
            [
                'liked' => false,
                'ip_address' => $request->ip()
            ]
        );
        return new JsonResponse([], 200);
    }

    /**
     * Like a reply.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Video  $video
     * @param  \App\Models\Comment  $comment
     * @param  \App\Models\Comment  $reply
     * @return \Illuminate\Http\Response
     */
    public function likeReply(Request $request, Video $video, Comment $comment, Comment $reply)
    {
        $this->authorize('like', $reply);
        $reply->interactions()->updateOrCreate(
            [
                'user_id' => $request->user()->id
            ],
            [
                'liked' => true,
                'ip_address' => $request->ip()
            ]
        );
        return new JsonResponse([], 200);
    }

    /**
     * Dislike a reply.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Video  $video
     * @param  \App\Models\Comment  $comment
     * @param  \App\Models\Comment  $reply
     * @return \Illuminate\Http\Response
     */
    public function dislikeReply(Request $request, Video $video, Comment $comment, Comment $reply)
    {
        $this->authorize('dislike', $reply);
        $reply->interactions()->updateOrCreate(
            [
                'user_id' => $request->user()->id
            ],
            [
                'liked' => false,
                'ip_address' => $request->ip()
            ]
        );
        return new JsonResponse([], 200);
    }

    public function report(Request $request, Video $video, Comment $comment)
    {
        $this->authorize('report', $comment);
        $request->validate(['content' => 'required|string|max:2000']);
        $video->reports()->create([
            'user_id' => $request->user()->id,
            'comment_id' => $comment->id,
            'content' => $request->content
        ]);
        return new JsonResponse([], 200);
    }
}
