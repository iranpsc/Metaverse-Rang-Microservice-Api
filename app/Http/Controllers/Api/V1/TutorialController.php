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
        if(request()->routeIs('tutorials-temp-url'))
        {
            request()->validate(['url' => 'required|string|max:255']);

            $video = Video::where('fileName', 'like', request()->input('url') . '%')
            ->with(['interactions', 'categoriable', 'views'])
            ->first();
            return $video ? new VideoTutorialResource($video) : [];
        }

        if (request()->query('modal')) {
            $video = Video::where('fileName', 'like', request()->query('modal') . '%')
                ->with(['interactions', 'categoriable', 'views'])
                ->first();
            if ($video)
            {
                $video->views()->updateOrCreate(
                    ['ip_address' => request()->ip()],
                    ['ip_address' => request()->ip()]
                );
            }
            return $video ? new VideoTutorialResource($video) : [];
        } else {
            $tutorials = Video::with(['interactions', 'categoriable', 'views'])
                ->orderByDesc('created_at')
                ->simplePaginate(18);
            return VideoTutorialResource::collection($tutorials);
        }
    }

    public function show(Video $video)
    {
        $video->views()->updateOrCreate(
            ['ip_address' => request()->ip()],
            ['ip_address' => request()->ip()]
        );
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
        $video->interactions()->updateOrCreate(
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
    public function dislike(Request $request, Video $video)
    {
        $video->interactions()->updateOrCreate(
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

    public function search(Request $request)
    {
        $tutorials = Video::where('title', 'like', '%' . $request->searchTerm . '%')
            ->with(['interactions', 'categoriable', 'views'])->simplePaginate(18);
        return VideoTutorialResource::collection($tutorials);
    }
}
