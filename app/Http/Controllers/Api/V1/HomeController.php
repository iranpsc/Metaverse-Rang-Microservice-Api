<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Option;
use App\Models\User;
use App\Http\Resources\PackageResource;
use App\Http\Resources\TopPlayerResource;
use App\Models\Video;
use App\Repositories\FeatureRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    private $user;

    public function __construct(
        private FeatureRepository $featureRepository,
        private UserRepository $userRepository,
    ) {

        $this->user = Auth::guard('sanctum')->user();
    }

    /**
     * @return array
     */
    public function index()
    {
        if ($this->user) {
            $data['user'] = new UserResource($this->user);
            $data['feature_hourly_profit_info'] =
                $this->user->features->count() > 0
                ? hourlyProfitInfo($this->user)
                : [];
        } else {
            $data['top_players'] = $this->userRepository->getTopPlayers();
        }
        $data['features'] = $this->featureRepository->getHomePageFeatures();
        return response()->json($data);
    }

    public function showUserDetails(User $user): TopPlayerResource
    {
        return new TopPlayerResource($user);
    }

    public function store()
    {
        return PackageResource::collection(Option::all());
    }

    public function filterByTypeAndCount(Request $request)
    {
        $request->validate([
            'codes' => 'required|array|min:2',
            'codes.*' => 'required|string|min:2'
        ]);
        $packages = Option::whereIn('code', $request->codes)->get();
        return PackageResource::collection($packages);
    }

    public function getTutorials(Request $request)
    {
        $request->validate(['url' => 'required|string']);
        $tutorial = Video::select(['title', 'description', 'fileName', 'image', 'creator_code'])
            ->where('fileName', 'like', $request->url . '%')->first();

        return $tutorial
            ? response()->json([
                'title' => $tutorial->title,
                'description' => $tutorial->description,
                'creator' => $tutorial->creator_code,
                'video' => $tutorial->fileName,
                'image' => $tutorial->image,
            ])
            : null;
    }
}
