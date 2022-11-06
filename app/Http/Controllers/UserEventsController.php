<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportEventRequest;
use App\Models\User\UserEvent;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class UserEventsController extends Controller
{
    public function index()
    {
        return UserEvent::where('user_id', request()->user()->id)
            ->with('report', 'report.responses')
            ->lazy()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'event' => $event->event,
                    'ip' => $event->ip,
                    'device' => $event->device,
                    'status' => $event->status ? 'موفق' : 'ناموفق',
                    'event_date' => Jalalian::forge($event->created_at)->format('Y/m/d'),
                    'event_time' => Jalalian::forge($event->created_at)->format('H:m:s'),
                    'report' => $event->report ?
                        [
                            'id' => $event->report->id,
                            'suspecious_citizen' => $event->report->suspecious_citizen,
                            'event_description' => $event->report->event_description,
                            'status' => $event->report->status,
                            'closed' => $event->report->closed,
                            'reported_date' => Jalalian::forge($event->report->created_at)->format('Y/m/d'),
                            'reported_time' => Jalalian::forge($event->report->created_at)->format('H:m:s'),
                            'responses' => $event->report->responses ?
                                $event->report->responses->map(function ($response) {
                                    return [
                                        'id' => $response->id,
                                        'responser_name' => $response->responser_name,
                                        'response' => $response->response,
                                        'responsed_date' => Jalalian::forge($response->created_at)->format('Y/m/d'),
                                        'responsed_time' => Jalalian::forge($response->created_at)->format('H:m:s'),
                                    ];
                                })
                                : "",
                        ]
                        : "",
                ];
            });
    }

    public function store(ReportEventRequest $request, UserEvent $userEvent)
    {
        $userEvent->report()->create([
            'suspecious_citizen' => $request->suspecious_citizen,
            'event_description' => $request->event_description
        ]);

        return response()->json(['success' => 'گزارش شما ارسال گردید و در حال بررسی می باشد'], 200);
    }

    public function sendResponse(Request $request, UserEvent $userEvent)
    {
        $this->validate(
            $request,
            [
                'response' => 'required|max:300'
            ],
            [
                'response.required' => 'متن پاسخ را وارد کنید',
                'response.max' => 'تعداد مجاز حداکثر کاراکتر 300 می باشد'
            ]
        );

        $userEvent->report->responses()->create([
            'responser_name' => $request->user()->name,
            'response' => $request->response,
        ]);

        $userEvent->report->update(['status' => 1]);
        return response()->json(['success' => 'پاسخ شما ارسال گردید'], 200);
    }

    public function closeEventReport(UserEvent $userEvent)
    {
        $userEvent->report->update(['closed' => 1]);
        return response()->json(['success' => 'گزارش بسته شد'], 200);
    }
}
