<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserAuthenticationTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_users_can_login()
    {
        $response = $this->withoutExceptionHandling()->post('/api/login', [
            'email' => 'abbas.ajorlou1371@gmail.com',
            'password' => '1234568@Amir'
        ]);

        $response->assertOk();
    }
}
