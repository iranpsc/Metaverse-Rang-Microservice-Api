<?php

namespace App\Http\Controllers;

use App\Http\Resources\SearchFeatureResultResource;
use App\Http\Resources\SearchUserResultResource;
use App\Models\FeatureProperties;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SearchController extends Controller
{
    /**
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function users(Request $request): AnonymousResourceCollection
    {
        $users = User::where('name', 'like', '%' . $request->searchTerm . '%')
            ->orWhere('code', 'like', '%' . $request->searchTerm . '%')
            ->with(['profilePhotos'])
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
            ->with(['feature', 'feature.owner'])
            ->take(5)
            ->get();
        return SearchFeatureResultResource::collection($features);
    }
}
