<?php

namespace App\Http\Controllers\Api\V1\Feature;

use App\Http\Controllers\Controller;
use App\Http\Requests\FeatureImageRequest;
use App\Http\Resources\FeatureImageResource;
use App\Http\Resources\UserFeatureResource;
use Illuminate\Http\JsonResponse;
use App\Models\Feature;
use App\Models\Image;
use App\Models\User;
use App\Models\Variable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeatureController extends Controller
{
    public function __construct()
    {
        $this->middleware(['account.security', 'verified'])->except(['index', 'show']);
    }
    /**
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        return UserFeatureResource::collection(
            Feature::whereBelongsTo(request()->user(), 'owner')->simplePaginate(5)
        );
    }

    /**
     * @param User $user
     * @param Feature $feature
     * @return UserFeatureResource
     */
    public function show(User $user, Feature $feature): UserFeatureResource
    {
        return new UserFeatureResource($feature);
    }

    /**
     * @param User $user
     * @param Feature $feature
     * @param FeatureImageRequest $request
     * @return JsonResponse
     */
    public function addFeatureImages(User $user, Feature $feature, FeatureImageRequest $request)
    {
        foreach ($request->file('images') as $image) {
            $url = $image->store('public/feature-images/' . $feature->id);
            $feature->images()->create(['url' => $url]);
        }
        return FeatureImageResource::collection($feature->images);
    }

    /**
     * @param Feature $feature
     * @param Image $image
     * @return JsonResponse
     */
    public function removeّFeatureImage(User $user, Feature $feature, Image $image)
    {
        $image->delete();
        return response()->noContent();
    }

    public function updateFeature(User $user, Feature $feature, Request $request)
    {
        $request->validate([
            'minimum_price_percentage' => [
                'required',
                'integer',
                'min:80',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->user()->isUnderEighteen() && $value < 110) {
                        $fail($attribute . ' must be greater than 110');
                    }
                }
            ]
        ]);

        $color = $feature->getColor();
        $totalPrice = $feature->properties->stability * Variable::getRate($color) * $request->minimum_price_percentage / 100;
        $price_psc = $totalPrice * 0.5 / Variable::getRate('psc');
        $price_irr = $totalPrice * 0.5;
        $feature->properties->update([
            'price_psc' => $price_psc,
            'price_irr' => $price_irr,
            'minimum_price_percentage' => $request->minimum_price_percentage
        ]);

        return response()->noContent();
    }
}
