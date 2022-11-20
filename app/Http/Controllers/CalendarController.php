<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Calendar;
use App\Http\Resources\EventResource;

class CalendarController extends Controller
{
    private $events ;

    public function __construct() {
        $this->events = Calendar::with('image', 'likes', 'dislikes')->lazy();
    }
    public function getEvents()
    {
        return EventResource::collection($this->events);
    }

    public function getSingleEvent(Calendar $event)
    {
        $event->increment('views');
        return new EventResource($event);
    }

    public function like(Request $request, Calendar $event)
    {
        if (! $event->likes->where('ip', $request->ip())->first()) {
            $event->likes()->create(['ip' => $request->ip()]);
        }
        return new EventResource($event->refresh());
    }
    public function dislike(Request $request, Calendar $event)
    {
        if (! $event->dislikes->where('ip', $request->ip())->first()) {
            $event->dislikes()->create(['ip' => $request->ip()]);
        }
        return new EventResource($event->refresh());
    }
}
