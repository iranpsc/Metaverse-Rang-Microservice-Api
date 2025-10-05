<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use App\Http\Resources\VideoTutorialResource;
use Illuminate\Http\JsonResponse;
use App\Models\VideoCategory;
use App\Http\Resources\V2\VideoCategoryResource;
use App\Http\Resources\V2\VideoSubCategoryResource;
use App\Models\VideoSubCategory;

class TutorialController extends Controller
{

    public function index()
    {
        $videos = Video::with(['subCategory.category', 'creator.profilePhotos'])
            ->latest()->paginate(18);

        return VideoTutorialResource::collection($videos);
    }

    public function show(Video $video)
    {
        $video->load(['subCategory.category', 'creator:id,code,name', 'creator.profilePhotos' => function ($query) {
            $query->limit(1);
        }]);

        $video->incrementViews();

        return new VideoTutorialResource($video);
    }

    public function showModalTutorial(Request $request)
    {
        $request->validate(['url' => 'required|string']);

        $video = Video::where('fileName', 'like', '%' . $request->url . '%')->firstOrFail();

        $video->incrementViews();

        return response()->json([
            'data' => [
                'id' => $video->id,
                'title' => $video->title,
                'description' => $video->description,
                'video' => $video->video_url,
                'image' => $video->image_url,
                'views' => $video->views_count,
                'likes' => $video->likes_count,
                'dislikes' => $video->dislikes_count,
                'creator_code' => $video->creator_code,
            ]
        ]);
    }

    /**
     * Handle like or dislike interaction for a video.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Video  $video
     * @return \Illuminate\Http\Response
     */
    public function interactions(Request $request, Video $video)
    {
        $request->validate([
            'liked' => 'required|boolean'
        ]);

        $liked = $request->query('liked');

        $video->interactions()->updateOrCreate(
            [
                'user_id' => $request->user()->id
            ],
            [
                'liked' => $liked,
                'ip_address' => $request->ip()
            ]
        );

        return new JsonResponse([], 200);
    }

    public function search(Request $request)
    {
        $request->validate(['searchTerm' => 'required|string']);

        $tutorials = Video::where('title', 'like', '%' . $request->searchTerm . '%')
            ->with(['creator:id,code', 'subCategory.category', 'creator.profilePhotos' => function ($query) {
                $query->limit(1);
            }])
            ->get();

        return response()->json([
            'data' => $tutorials->map(function ($tutorial) {
                $category = $tutorial->subCategory->category ? [
                    'name' => $tutorial->subCategory->category->name,
                    'slug' => $tutorial->subCategory->category->slug,
                ] : null;

                $subCategory = $tutorial->subCategory ? [
                    'name' => $tutorial->subCategory->name,
                    'slug' => $tutorial->subCategory->slug,
                ] : null;

                return [
                    'id' => $tutorial->id,
                    'title' => $tutorial->title,
                    'slug' => $tutorial->slug,
                    'likes_count' => $tutorial->likes_count,
                    'dislikes_count' => $tutorial->dislikes_count,
                    'views_count' => $tutorial->views_count,
                    'category' => $category,
                    'sub_category' => $subCategory,
                    'creator' => [
                        'code' => $tutorial->creator_code,
                        'image' => optional($tutorial->creator->profilePhotos->last())->url,
                    ]
                ];
            })
        ]);
    }

    public function getCategories()
    {
        $categories = VideoCategory::withCount(['videos', 'views', 'likes', 'dislikes'])
            ->orderByDesc('likes_count')
            ->paginate(request()->query('count', 30));

        return VideoCategoryResource::collection($categories);
    }

    public function showCategory(VideoCategory $category)
    {
        $category->load(['subCategories' => function ($query) {
            $query->withCount(['videos', 'views', 'likes', 'dislikes']);
        }])->loadCount([
            'views',
            'likes',
            'dislikes',
            'videos',
        ]);

        return new VideoCategoryResource($category);
    }

    public function showSubCategory(VideoCategory $category, VideoSubCategory $subCategory)
    {
        $subCategory->load(['videos', 'videos.creator:id,code', 'category', 'videos.creator.profilePhotos' => function ($query) {
            $query->limit(1);
        }])
        ->loadCount('likes', 'views', 'dislikes');

        return new VideoSubCategoryResource($subCategory);
    }

    public function showCategoryVideos(VideoCategory $category)
    {
        $videos = $category->videos()
            ->with(['subCategory.category', 'creator:id,code,name', 'creator.profilePhotos' => function ($query) {
                $query->limit(1);
            }])
            ->withCount(['views', 'likes', 'dislikes'])
            ->latest()
            ->paginate(request()->query('per_page', 18));

        return VideoTutorialResource::collection($videos);
    }
}
