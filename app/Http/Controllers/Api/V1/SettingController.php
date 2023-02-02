<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateGeneralSettingsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        $settings = auth()->user()->settings;

        if ($request->has('checkout_days_count')) {
            $request->validate([
                'checkout_days_count' => 'required|numeric|min:3',
                'automatic_logout' => 'required|integer|min:5',
            ]);

            $settings->update([
                'checkout_days_count' => $request->checkout_days_count,
                'automatic_logout' => $request->automatic_logout,
            ]);
        }

        if ($request->has('setting')) {
            $request->validate([
                'setting' => 'required|in:status,level,details',
                'status' => 'required|boolean',
            ]);
            $settings->update([
                $request->input('setting') => $request->input('status'),
            ]);
        }

        return response()->noContent(200);
    }

    public function generalSettingsUpdate(UpdateGeneralSettingsRequest $request)
    {
        $request->user()->generalSettings->update([
            $request->input('setting') => $request->input('status'),
        ]);
        return response()->noContent(200);
    }


    public function uploadProfilePhoto(Request $request)
    {
        $request->validate(['image' => 'required|image|mimes:png,jpg,jpeg|max:1024']);
        $url = $request->file('image')->store('user/profile');
        $request->user()->profilePhotos()->create(['url' => $url]);
        return response()->noContent(200);
    }
}
