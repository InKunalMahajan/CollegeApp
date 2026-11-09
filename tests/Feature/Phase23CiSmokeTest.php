<?php

namespace Tests\Feature;

use Tests\TestCase;

class Phase23CiSmokeTest extends TestCase
{
    public function test_health_endpoint_responds_to_a_guest(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_guest_cannot_read_a_school_dashboard(): void
    {
        $this->get('/schools/999999/dashboard')->assertRedirect('/login');
    }
}
