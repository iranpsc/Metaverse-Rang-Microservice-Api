<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use App\Http\Resources\VideoTutorialResource;
use Illuminate\Http\JsonResponse;

class TutorialController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(request()->query('modal'))
        {
            $tutorial = Video::where('fileName', 'like', request()->query('modal') . '%')
            ->with(['likes', 'dislikes', 'categoriable'])
            ->first();
            if ($tutorial) $tutorial->increment('visits');
            return $tutorial ? new VideoTutorialResource($tutorial) : [];
        } else {
            $tutorials = Video::with(['likes', 'dislikes', 'categoriable'])->simplePaginate(18);
            return VideoTutorialResource::collection($tutorials);
        }
    }

    public function show(Video $video)
    {
        return new VideoTutorialResource($video);
    }

    /**
     * Like the video.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function like(Request $request, Video $video)
    {
        $video->likes()->create(['ip' => $request->ip()]);
        return new JsonResponse([], 200);
    }

    /**
     * Dislike the video.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Video  $video
     * @return \Illuminate\Http\Response
     */
    public function dislike(Request $request, Video $video)
    {
        $video->dislikes()->create(['ip' => $request->ip()]);
        return new JsonResponse([], 200);
    }

    public function search(Request $request)
    {
        $tutorials = Video::where('title', 'like', '%'. $request->searchTerm .'%')
        ->with(['likes', 'dislikes', 'categoriable'])->simplePaginate(18);
        return VideoTutorialResource::collection($tutorials);
    }
}
