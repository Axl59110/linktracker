<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * L'application redirige vers login si non authentifié.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        // The app redirects unauthenticated users to login
        $response->assertStatus(302);
    }
}
