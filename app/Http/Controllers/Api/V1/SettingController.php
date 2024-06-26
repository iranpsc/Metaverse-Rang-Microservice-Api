<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SettingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Http\Requests\UpdatePrivacyRequest;
use App\Http\Requests\UpdateSettingNotificationsRequest;
use App\Http\Resources\NotificationSettingsResource;

class SettingController extends Controller
{
    /**
     * Show the settings
     *
     * @return SettingResource
     */
    public function showSettings()
    {
        return new SettingResource(request()->user()->settings);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        $settings = auth()->user()->settings;

        if ($request->has('checkout_days_count')) {
            $request->validate([
                'checkout_days_count' => 'required|integer|min:3|max:1000',
                'automatic_logout' => 'required|integer|min:1|max:55',
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

        return response()->noContent();
    }

    /**
     * Get the General setting info
     *
     * @return NotificationSettingsResource
     */
    public function showGeneralSettings()
    {
        $settings = request()->user()->settings;

        return new NotificationSettingsResource($settings);
    }

    /**
     * Update the General setting info
     *
     * @param UpdateSettingNotificationsRequest $request
     * @param Setting $setting
     * @return JsonResponse
     */
    public function updateGeneralSettings(UpdateSettingNotificationsRequest $request, Setting $setting)
    {
        $setting->update(['notifications' => $request->only([
            'announcements_sms',
            'announcements_email',
            'reports_sms',
            'reports_email',
            'login_verification_sms',
            'login_verification_email',
            'transactions_sms',
            'transactions_email',
            'trades_sms',
            'trades_email',
        ])]);

        return new NotificationSettingsResource($setting->refresh());
    }

    /**
     * Get the Privacy setting info
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPrivacySettings(Request $request)
    {
        return response()->json(['data' => $request->user()->settings->privacy]);
    }

    /**
     * Update the Privacy setting info
     *
     * @param UpdatePrivacyRequest $request
     * @return JsonResponse
     */
    public function updatePrivacySettings(UpdatePrivacyRequest $request)
    {
        $request->user()->settings->update([
            'privacy->' . $request->string('setting') => $request->boolean('value'),
        ]);

        return response()->noContent();
    }
}
