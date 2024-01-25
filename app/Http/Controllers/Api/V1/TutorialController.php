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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->routeIs('tutorials-temp-url')) {
            request()->validate(['url' => 'required|string|max:255']);

            $video = Video::where('fileName', 'like', '%' . request()->input('url') . '%')
                ->with(['interactions', 'subCategory.category', 'views', 'creator'])
                ->first();

            if ($video) $video->incrementViews();

            return $video ? new VideoTutorialResource($video) : [];
        }

        if (request()->query('modal')) {
            $video = Video::where('fileName', 'like', '%' . request()->query('modal') . '%')
                ->with(['interactions', 'subCategory.category', 'views', 'creator'])
                ->first();
            if ($video) $video->incrementViews();
            return $video ? new VideoTutorialResource($video) : [];
        } else {
            $tutorials = Video::with([
                'subCategory.category',
                'creator.profilePhotos'
            ])
                ->withCount([
                    'views',
                    'interactions as likes' => function ($query) {
                        $query->where('liked', 1);
                    },
                    'interactions as dislikes' => function ($query) {
                        $query->where('liked', 0);
                    }
                ])
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

        $video->load('subCategory.category', 'creator.profilePhotos')
            ->loadCount([
                'views',
                'interactions as likes' => function ($query) {
                    $query->where('liked', 1);
                },
                'interactions as dislikes' => function ($query) {
                    $query->where('liked', 0);
                }
            ]);

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
        $request->validate(['searchTerm' => 'required|string|max:255']);

        $tutorials = Video::where('title', 'like', '%' . $request->searchTerm . '%')
            ->select('id', 'title', 'creator_code')
            ->with(['creator.profilePhotos' => function ($query) {
                $query->limit(1);
            }])
            ->withCount(['interactions as likes' => function ($query) {
                $query->where('liked', 1);
            }])
            ->get();

        return response()->json([
            'data' => [
                $tutorials->map(function ($tutorial) {
                    return [
                        'id' => $tutorial->id,
                        'title' => $tutorial->title,
                        'likes' => $tutorial->likes,
                        'creator_code' => $tutorial->creator->code,
                        'creator_image' => optional($tutorial->creator->profilePhotos->last())->url,
                    ];
                })
            ]
        ]);
    }

    public function categories()
    {
        // Get 8 categories with most veiws of the videos in their subcategories
        $categories = VideoCategory::with(['subCategories' => function ($query) {
            $query->withCount(['videos' => function ($query) {
                $query->with('views')->withCount('views');
            }]);
        }])->withCount(['subCategories' => function ($query) {
            $query->withCount(['videos' => function ($query) {
                $query->with('views')->withCount('views');
            }]);
        }])->orderByDesc('sub_categories_count')->paginate(request()->query('count', 30));
        return VideoCategoryResource::collection($categories);
    }

    // View a single category with its subcategories and videos
    public function category(VideoCategory $category)
    {
        return new VideoCategoryResource($category);
    }

    // View Single subcategory
    public function subCategory(VideoCategory $category, VideoSubCategory $subCategory)
    {
        return new VideoSubCategoryResource($subCategory->load('videos'));
    }
}
