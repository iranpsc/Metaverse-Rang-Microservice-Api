<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Feature;
use App\Models\Option;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\PackageResource;
use App\Http\Resources\TopPlayerResource;
use Morilog\Jalali\Jalalian;

class HomeController extends Controller
{

    /**
     * @return array
     */
    public function index(Request $request): array
    {
        return [
            'user' => $request->user('sanctum') ? new UserResource($request->user('sanctum')) : [],
            'top_players' => !$request->user('sanctum')
                ? User::orderBy('score', 'DESC')->take(10)->get()->map(function($user) {
                    return [
                        'id' => $user->id,
                        'code' => $user->code,
                        'score' => $user->score,
                        'profile-photo' => $user->profilePhoto->url ?? "",
                        'level' => $user->level,
                    ];
                })  : [],
            'features' => Feature::with(['properties', 'geometry.coordinates'])->lazyById()->map(function ($feature) {
                return [
                    'id'         => $feature->id,
                    'owner_id'   => $feature->owner_id,
                    'properties' => [
                        'id'                       => $feature->properties->id,
                        'address'                  => $feature->properties->address,
                        'density'                  => $feature->properties->density,
                        'stability'                => $feature->properties->stability,
                        'label'                    => $feature->properties->label,
                        'area'                     => $feature->properties->area,
                        'region'                   => $feature->properties->region,
                        'karbari'                  => $feature->properties->karbari,
                        'owner'                    => $feature->properties->owner,
                        'rgb'                      => $feature->properties->rgb,
                        'price_psc'                => $feature->properties->price_psc,
                        'price_irr'                => $feature->properties->price_irr,
                        'minimum_price_percentage' => $feature->properties->minimum_price_percentage,
                        'created_at'               => Jalalian::forge($feature->properties->created_at)->format('Y/m/d'),
                    ],
                    'geometry'  => [
                        'type'        => $feature->geometry->type,
                        'coordinates' => $feature->geometry->coordinates->map(function ($coordinate) {
                            return [
                                'x' => $coordinate->x,
                                'y' => $coordinate->y
                            ];
                        })
                    ]
                ];
            })
        ];
    }

    public function showUserDetails(User $user)
    {
        return new TopPlayerResource($user);
    }

    public function store()
    {
        return PackageResource::collection(Option::lazy());
    }
}
