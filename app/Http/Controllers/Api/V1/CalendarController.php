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
            'getLatestVersionEvent'
        ]);
    }

    /**
     * Display a listing of the events.
     * @return \Illuminate\Http\Response
     */
    public function getEvents()
    {
        $events = Calendar::currentEvents()->with(['interactions', 'views'])
            ->orderBy('starts_at', 'desc')
            ->get();
        return EventResource::collection($events);
    }

    /**
     * Display the specified resource.
     * @param  \App\Models\Calendar  $event
     * @return \Illuminate\Http\Response
     */
    public function getSingleEvent(Calendar $event)
    {
        $event->incrementViews();
        return new EventResource($event);
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Response
     */
    public function getVersionsEvents()
    {
        $events = Calendar::versionEvents()->with(['interactions', 'views'])
            ->orderBy('starts_at', 'desc')
            ->paginate(20);
        return EventResource::collection($events);
    }

    /**
     * Display the specified resource.
     * @param  \App\Models\Calendar  $versionEvent
     * @return \Illuminate\Http\Response
     */
    public function getVersionEvent(Calendar $versionEvent)
    {
        $versionEvent->incrementViews();
        return new EventResource($versionEvent);
    }

    /**
     * Like the event
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Calendar  $event
     * @return \Illuminate\Http\Response
     */
    public function likeEvent(Request $request, Calendar $event)
    {
        $event->interactions()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['liked' => 1]
        );
        return new EventResource($event->refresh());
    }

    /**
     * Dislike the event
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Calendar  $event
     * @return \Illuminate\Http\Response
     */
    public function dislikeEvent(Request $request, Calendar $event)
    {
        $event->interactions()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['liked' => 0]
        );
        return new EventResource($event->refresh());
    }

    public function getLatestVersionEvent()
    {
        $event = Calendar::versionEvents()->latest('starts_at')->pluck('version_title')->first();
        return response()->json([
            'data' => [
                'version_title' => $event
            ]
        ]);
    }
}
