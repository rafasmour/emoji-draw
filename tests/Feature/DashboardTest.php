<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_auto_logged_in_as_guests()
    {
        $this->assertGuest();
        $this->get(route('dashboard'))->assertOk();
        $this->assertAuthenticated();
        $this->assertTrue((bool) auth()->user()->is_guest);
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $this->actingAs($user = User::factory()->create());

        $this->get(route('dashboard'))->assertOk();
    }
}
