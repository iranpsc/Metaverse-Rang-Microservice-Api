<?php

namespace App\Http\Controllers\Api\V1\Dynasty;

use App\Http\Requests\CreateDynastyRequest;
use App\Http\Resources\Dynasty\DynastyResource;
use App\Models\Feature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dynasty\IntroductionPrizeResource;
use App\Models\Dynasty\Dynasty;
use App\Models\Dynasty\DynastyPrize;
use App\Models\LockedFeature;
use App\Notifications\DynastyCreatedNotification;
use App\Notifications\DynastyFeatureChangedNotification;

class DynastyController extends Controller
{
    public function __construct()
    {
        $this->middleware('account.security')->except('index');
    }

    public function index(Request $request): JsonResponse|DynastyResource
    {
        $dynasty = Dynasty::whereBelongsTo($request->user())->with([
            'family',
            'family.familyMembers',
            'feature',
            'user'
        ])->first();

        if (is_null($dynasty)) {
            $features =  $request->user()->features
                ->reject(function ($feature) {
                    return $feature->properties->karbari !== 'm';
                })
                ->map(function ($feature) {
                    return [
                        'id' => $feature->id,
                        'properties_id' => $feature->properties->id,
                        'stability' => $feature->properties->stability
                    ];
                });

            return response()->json([
                'data' => [
                    'user-has-dynasty' => false,
                    'features' => $features,
                    'prizes' => IntroductionPrizeResource::collection(DynastyPrize::all())
                ]
            ]);
        }
        return new DynastyResource($dynasty);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateDynastyRequest $request
     * @param Feature $feature
     * @return DynastyResource|JsonResponse
     */
    public function store(Request $request, Feature $feature): DynastyResource|JsonResponse
    {
        $this->authorize('create', [Dynasty::class, $feature]);

        $dynasty = $request->user()->dynasty()->create([
            'feature_id' => $feature->id,
        ]);

        $family = $dynasty->family()->create();

        $family->familyMembers()->create([
            'user_id' => $request->user()->id,
            'relationship' => 'owner'
        ]);

        $request->user()->notify(new DynastyCreatedNotification($feature->properties->id));

        return new DynastyResource($dynasty);
    }

    public function update(Dynasty $dynasty, Feature $feature, Request $request)
    {
        $this->authorize('update', [$dynasty, $feature]);

        $currentFeature = $dynasty->feature;

        $dynasty->update(['feature_id' => $feature->id]);

        if ($dynasty->updated_at->diffInDays(now()) < 30) {
            $request->user()->debts()->create([
                $currentFeature->getColor() => $currentFeature->properties->stability * 0.01,
                'reason' => 'update-dynasty-feature',
            ]);

            $currentFeature->properties->update(['label' => 'locked']);

            LockedFeature::create([
                'feature_id' => $currentFeature->id,
                'reason' => 'dynasty-feature-change',
                'until' => now()->addMonth(),
                'status' => 0,
            ]);
        }

        $request->user()->notify(new DynastyFeatureChangedNotification($feature->properties->id));

        return new DynastyResource($dynasty->refresh());
    }
}
