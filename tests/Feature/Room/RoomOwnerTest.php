<?php

namespace Tests\Feature\Room;

use App\DataObjects\RoomSettings;
use App\DataObjects\RoomStatus;
use App\Models\Room;
use App\Models\User;
use Tests\TestCase;

class RoomOwnerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Room::query()->delete();
        User::query()->delete();
    }

    protected function tearDown(): void
    {
        Room::query()->delete();
        User::query()->delete();
        parent::tearDown();
    }

    private function makeRoom(User $owner, array $extraUsers = []): Room
    {
        $users = [
            ['id' => $owner->id, 'name' => $owner->name, 'score' => 0, 'guesses' => 0, 'correct_guesses' => 0, 'guessed' => false, 'room_token' => null],
        ];

        foreach ($extraUsers as $user) {
            $users[] = ['id' => $user->id, 'name' => $user->name, 'score' => 0, 'guesses' => 0, 'correct_guesses' => 0, 'guessed' => false, 'room_token' => null];
        }

        return Room::create([
            'name' => 'Test Room',
            'owner' => $owner->id,
            'users' => $users,
            'settings' => new RoomSettings(difficulty: 'easy', public: true, cap: 8, rounds: 3, categories: [], language: 'en', timeLimit: 60),
            'chat' => [],
            'canvas' => [],
            'kicked_users' => [],
            'status' => new RoomStatus(started: false, round: 0, time: '0', term: '', guesses: 0),
        ]);
    }

    public function test_owner_can_change_ownership(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, [$other]);

        $this->actingAs($owner)
            ->patchJson(route('room.change.owner', $room), ['user_id' => $other->id])
            ->assertSuccessful();

        $room->refresh();
        $this->assertEquals($other->id, $room->owner);
    }

    public function test_non_owner_cannot_change_ownership(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, [$other]);

        $this->actingAs($other)
            ->patchJson(route('room.change.owner', $room), ['user_id' => $owner->id])
            ->assertStatus(403);

        $room->refresh();
        $this->assertEquals($owner->id, $room->owner);
    }

    public function test_owner_cannot_transfer_to_user_not_in_room(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $room = $this->makeRoom($owner);

        $this->actingAs($owner)
            ->patchJson(route('room.change.owner', $room), ['user_id' => $outsider->id])
            ->assertStatus(403);

        $room->refresh();
        $this->assertEquals($owner->id, $room->owner);
    }

    public function test_assign_random_owner_picks_from_existing_users(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, [$other]);

        app(\App\Http\Contracts\RoomOwnerServiceInterface::class)->assignRandomOwner($room);

        $room->refresh();
        $userIds = $room->users->pluck('id')->all();
        $this->assertContains($room->owner, $userIds);
    }
}
