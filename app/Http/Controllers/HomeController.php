<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Option;
use App\Models\User;
use App\Http\Resources\PackageResource;
use App\Http\Resources\TopPlayerResource;
use App\Repositories\FeatureRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{

    public function __construct(
        private FeatureRepository $featureRepository,
        private UserRepository $userRepository
    ) {
    }

    /**
     * @return array
     */
    public function index()
    {
        if (Auth::check()) {
            $data['user'] = new UserResource(Auth::user());
            $data['feature_hourly_profit_info'] =
                Auth::user()->features->count() > 0
                ? hourlyProfitInfo(Auth::user())
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

    public function store(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return PackageResource::collection(Option::lazy());
    }
}
