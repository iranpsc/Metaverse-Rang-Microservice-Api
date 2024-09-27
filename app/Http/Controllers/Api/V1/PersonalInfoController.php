<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePersonalInfoRequest;
use App\Models\User\PersonalInfo;
use Illuminate\Http\Request;

class PersonalInfoController extends Controller
{

    /**
     * Show user's personal info.
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        return response()->json([
            'data' => request()->user()->personalInfo->map(function ($info) {
                return [
                    'occupation' => $info->occupation,
                    'education' => $info->education,
                    'memory' => $info->memory,
                    'loved_city' => $info->loved_city,
                    'loved_country' => $info->loved_country,
                    'loved_language' => $info->loved_language,
                    'problem_solving' => $info->problem_solving,
                    'prediction' => $info->prediction,
                    'about' => $info->about,
                    'passions' => $info->passions,
                ];
            }),
        ]);
    }

    /**
     * Update user's personal info.
     *
     * @param  \Illuminate\Http\UpdatePersonalInfoRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePersonalInfoRequest $request)
    {
        PersonalInfo::updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated()
        );

        return response()->json([], 204);
    }
}
