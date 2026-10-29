<?php

namespace Tests\Feature;

use Tests\TestCase;

class PortalTest extends TestCase
{
    public function test_application_health_route_works(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }
}
