<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use App\Http\Resources\VideoCommentResource;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class VideoCommentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Video $video)
    {
        $comments = Comment::with('user')
            ->leftJoin('interactions', function ($join) {
                $join->on('comments.id', '=', 'interactions.likeable_id')
                    ->where('interactions.likeable_type', '=', Comment::class);
            })
            ->where('comments.commentable_id', $video->id)
            ->where('comments.commentable_type', Video::class)
            ->select('comments.*', DB::raw('COUNT(interactions.id) as interactions_count'))
            ->groupBy('comments.id')
            ->orderByDesc('interactions_count')
            ->with(['user', 'user.kyc:id,fname,lname'])
            ->simplePaginate(10);
        return VideoCommentResource::collection($comments);
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
        return new VideoCommentResource($comment);
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
     * Like the video.
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
     * Dislike the video.
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
