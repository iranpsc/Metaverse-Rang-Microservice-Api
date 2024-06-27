<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FollowResource;
use App\Http\Resources\PlayerProfileResource;
use App\Http\Resources\UserFeatureResource;
use App\Models\Feature;
use App\Models\Privacy;
use App\Models\User;
use App\Repositories\UserRepository;

class PlayerController extends Controller
{

    public function __construct(
        private UserRepository $userRepository
    ) {
        //
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(['data' => $this->userRepository->topUsers()]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function wallet(User $player)
    {
        $privacy = Privacy::whereUserId($player->id)
            ->select(['name', 'display'])
            ->whereIn('name', ['maskoni_features', 'tejari_features', 'amoozeshi_features'])
            ->get();
        $features = $player->features;

        $features = $features->reject(function ($feature) use ($privacy) {
            if (
                $feature->properties->karbari == 'a'
                && !$privacy->where('name', 'amoozeshi_features')->first()->display
            ) true;

            if (
                $feature->properties->karbari == 'm'
                && !$privacy->where('name', 'maskoni_features')->first()->display
            ) true;

            if (
                $feature->properties->karbari == 't'
                && !$privacy->where('name', 'tejari_features')->first()->display
            ) true;
        });

        return UserFeatureResource::collection($features);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function asset(User $player, Feature $feature)
    {
        return new UserFeatureResource($feature);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function profile(User $player)
    {
        $player->privacy = Privacy::whereUserId($player->id)->select(['name', 'display'])->get();
        return new PlayerProfileResource($player);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function followers(User $player)
    {
        $isAllowed = Privacy::whereUserId($player->id)->whereName('followers')->pluck('display')->first();
        return $isAllowed
            ? FollowResource::collection($player->followers)
            : null;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function following(User $player)
    {
        $isAllowed = Privacy::whereUserId($player->id)->whereName('following')->pluck('display')->first();
        return $isAllowed
            ? FollowResource::collection($player->following)
            : null;
    }
}
