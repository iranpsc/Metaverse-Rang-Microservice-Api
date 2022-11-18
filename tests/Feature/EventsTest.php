<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventsTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_application_fires_events_successfully()
    {
        $response = $this->withoutExceptionHandling()->get('/event');

        $response->assertOk();

    }
}
