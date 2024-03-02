<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SearchFeatureResultResource;
use App\Http\Resources\SearchUserResultResource;
use App\Models\FeatureProperties;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function users(Request $request): AnonymousResourceCollection
    {
        $searchTerms = explode(' ', $request->searchTerm);

        $users = User::where(function ($query) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $query->orWhere('name', 'like', '%' . $term . '%')
                    ->orWhere('code', 'like', '%' . $term . '%');
            }
        })
            ->orWhereHas('kyc', function ($query) use ($searchTerms) {
                $query->where(function ($query) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $query->orWhere('fname', 'like', '%' . $term . '%')
                            ->orWhere('lname', 'like', '%' . $term . '%');
                    }
                });
            })
            ->with(['profilePhotos', 'kyc:user_id,fname,lname'])
            ->take(5)
            ->get();
        return SearchUserResultResource::collection($users);
    }

    /**
     * @param Request $request
     * @return Response|JsonResponse|Application|ResponseFactory
     */
    public function features(Request $request)
    {
        $features = FeatureProperties::where('id', 'like', '%' . $request->searchTerm . '%')
            ->orWhere('address', 'like', '%' . $request->searchTerm . '%')
            ->with(['feature', 'feature.owner', 'feature.geometry.coordinates'])
            ->take(5)
            ->get();
        return SearchFeatureResultResource::collection($features);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function isicCodes(Request $request): JsonResponse
    {
        $isicCodes = DB::table('isic_codes')
            ->where('name', 'like', '%' . $request->searchTerm . '%')
            ->select('id', 'name', 'code')
            ->get();
        return response()->json(['data' => $isicCodes]);
    }
}
