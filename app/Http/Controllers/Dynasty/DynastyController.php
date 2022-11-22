<?php

namespace App\Http\Controllers\Dynasty;

use App\Constants\FamilyMembersType;
use App\Http\Requests\CreateDynastyRequest;
use App\Http\Resources\DynastyResource;
use App\Models\Feature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DynastyController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param CreateDynastyRequest $request
     * @param Feature $feature
     * @return DynastyResource|JsonResponse
     */
    public function store(Request $request, Feature $feature): DynastyResource|JsonResponse
    {
        if ($request->user()->cannot('createDynasty', $feature)) {
            abort(403, 'این ملک شرایط لازم جهت تاسیس سلسله را ندارد');
        }

        $dynasty = $request->user()->dynasty()->create([
            'feature_id' => $feature->id,
        ]);

        $family = $dynasty->family()->create();

        $family->familyMembers()->create([
            'user_id' => $request->user()->id,
            'relationship' => FamilyMembersType::OWNER
        ]);

        return DynastyResource::make($dynasty);
    }

}
