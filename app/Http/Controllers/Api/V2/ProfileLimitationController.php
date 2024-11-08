<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileLimitationResource;
use App\Models\ProfileLimitation;
use Illuminate\Http\Request;

class ProfileLimitationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');

        $this->authorizeResource(ProfileLimitation::class, 'profileLimitation');
    }

    /**
     * Store a newly created profile limitation in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'limited_user_id' => 'required|exists:users,id',
            'options' => 'required|array:follow,send_message,share,send_ticket,' .
                'view_profile_images,view_features_locations',
            'options.*' => 'required|boolean',
            'note' => 'nullable|string|max:500',
        ]);

        $profileLimitation = ProfileLimitation::create([
            'limiter_user_id' => auth()->id(),
            'limited_user_id' => $request->limited_user_id,
            'options' => $request->options,
            'note' => $request->note
        ]);

        return new ProfileLimitationResource($profileLimitation);
    }

    /**
     * Update the specified profile limitation in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  ProfileLimitation  $profileLimitation
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, ProfileLimitation $profileLimitation)
    {
        $request->validate([
            'options' => 'required|array:follow,send_message,share,send_ticket,' .
                'view_profile_images,view_features_locations',
            'options.*' => 'required|boolean',
            'note' => 'nullable|string|max:500',
        ]);

        $profileLimitation->update($request->only('options', 'note'));

        return new ProfileLimitationResource($profileLimitation);
    }

    /**
     * Remove the specified profile limitation from storage.
     *
     * @param  ProfileLimitation  $profileLimitation
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(ProfileLimitation $profileLimitation)
    {
        $profileLimitation->delete();

        return response()->noContent();
    }
}
