<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use App\Http\Resources\VideoCommentResource;
use App\Http\Resources\VideoTutorialResource;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class VideoCommentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Video $video
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Video $video)
    {
        $comments = $video->comments()
            ->with('user.latestProfilePhoto')
            ->withCount(['likes', 'dislikes', 'replies'])
            ->whereNull('parent_id') // Only get parent comments
            ->orderBy('likes_count', 'desc')
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
     * Like or dislike the comment based on the 'liked' query parameter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Video  $video
     * @param  \App\Models\Comment  $comment
     * @return \Illuminate\Http\Response
     */
    public function interactions(Request $request, Video $video, Comment $comment)
    {
        $request->validate(['liked' => 'required|boolean']);

        $likedBool = $request->input('liked');

        $this->authorize($likedBool ? 'like' : 'dislike', $comment);

        $comment->interactions()->updateOrCreate(
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
