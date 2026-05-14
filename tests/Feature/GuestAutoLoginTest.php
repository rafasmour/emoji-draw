<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestAutoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_visiting_room_index_is_auto_logged_in_as_guest(): void
    {
        $this->assertGuest();

        $response = $this->get(route('room.rooms'));

        $response->assertOk();
        $this->assertAuthenticated();

        $user = auth()->user();
        $this->assertTrue((bool) $user->is_guest);
        $this->assertStringStartsWith('Guest_', $user->name);
        $this->assertNull($user->password);
    }

    public function test_guest_user_record_is_persisted_in_database(): void
    {
        $this->get(route('room.rooms'));

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['is_guest' => true]);
    }

    public function test_already_authenticated_real_user_passes_through_without_creating_new_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('room.rooms'))->assertOk();

        $this->assertDatabaseCount('users', 1);
        $this->assertEquals($user->getKey(), auth()->id());
    }

    public function test_already_authenticated_guest_passes_through_without_creating_new_account(): void
    {
        $guest = User::factory()->create(['is_guest' => true]);

        $this->actingAs($guest)->get(route('room.rooms'))->assertOk();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_guest_user_can_visit_login_page(): void
    {
        $guest = User::factory()->create(['is_guest' => true]);

        $this->actingAs($guest)->get(route('login'))->assertOk();
    }

    public function test_guest_user_can_visit_register_page(): void
    {
        $guest = User::factory()->create(['is_guest' => true]);

        $this->actingAs($guest)->get(route('register'))->assertOk();
    }

    public function test_real_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('login'))->assertRedirect();
    }
}
