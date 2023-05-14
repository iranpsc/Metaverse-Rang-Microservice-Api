<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Calendar;
use App\Http\Resources\EventResource;

class CalendarController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:sanctum')->except([
            'getEvents',
            'getSingleEvent',
            'getVersionsEvents',
            'getVersionEvent',
        ]);
    }

    public function getEvents()
    {
        $events = Calendar::where('is_version', 0)
            ->whereDate('ends_at', '>', now())
            ->with(['interactions', 'views'])->get();
        return EventResource::collection($events);
    }

    public function getSingleEvent(Calendar $event)
    {
        $event->incrementViews();
        return new EventResource($event);
    }

    public function getVersionsEvents()
    {
        $events = Calendar::where('is_version', 1)
            ->whereDate('ends_at', '>', now())
            ->with(['interactions', 'views'])->get();
        return EventResource::collection($events);
    }

    public function getVersionEvent(Calendar $versionEvent)
    {
        $versionEvent->incrementViews();
        return new EventResource($versionEvent);
    }

    public function likeEvent(Request $request, Calendar $event)
    {
        $event->interactions()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['liked' => 1]
        );
        return new EventResource($event->refresh());
    }

    public function dislikeEvent(Request $request, Calendar $event)
    {
        $event->interactions()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['liked' => 0]
        );
        return new EventResource($event->refresh());
    }
}
