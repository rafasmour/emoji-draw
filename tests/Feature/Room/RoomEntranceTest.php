<?php

namespace Tests\Feature\Room;

use App\DataObjects\RoomSettings;
use App\DataObjects\RoomStatus;
use App\DataObjects\RoomUser;
use App\Models\Room;
use App\Models\User;
use Tests\TestCase;

class RoomEntranceTest extends TestCase
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

    private function makeRoom(User $owner, array $extraUsers = [], int $cap = 8): Room
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
            'settings' => new RoomSettings(difficulty: 'easy', public: true, cap: $cap, rounds: 3, categories: [], language: 'en', timeLimit: 60),
            'chat' => [],
            'canvas' => [],
            'started' => false,
            'status' => new RoomStatus(started: false, round: 0, time: '0', term: '', guesses: 0),
        ]);
    }

    public function test_user_can_join_room(): void
    {
        $owner = User::factory()->create();
        $joiner = User::factory()->create();
        $room = $this->makeRoom($owner);

        $this->actingAs($joiner)
            ->post(route('room.join'), ['room_id' => $room->id])
            ->assertRedirect(route('room.lobby', $room));

        $room->refresh();
        $this->assertCount(2, $room->users);
        $this->assertInstanceOf(RoomUser::class, $room->users->last());
        $this->assertEquals($joiner->id, $room->users->last()->id);
    }

    public function test_user_cannot_join_full_room(): void
    {
        $owner = User::factory()->create();
        $joiner = User::factory()->create();
        $room = $this->makeRoom($owner, cap: 1);

        $this->actingAs($joiner)
            ->post(route('room.join'), ['room_id' => $room->id])
            ->assertOk();

        $room->refresh();
        $this->assertCount(1, $room->users);
    }

    public function test_user_can_leave_room(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, [$other]);

        $this->actingAs($other)
            ->post(route('room.leave', $room))
            ->assertRedirect(route('room.rooms'));

        $room->refresh();
        $this->assertCount(1, $room->users);
        $this->assertEquals($owner->id, $room->users->first()->id);
    }

    public function test_last_user_leaving_deletes_room(): void
    {
        $owner = User::factory()->create();
        $room = $this->makeRoom($owner);

        $this->actingAs($owner)
            ->post(route('room.leave', $room))
            ->assertRedirect(route('room.rooms'));

        $this->assertNull(Room::find($room->id));
    }

    public function test_owner_can_kick_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, [$other]);

        $this->actingAs($owner)
            ->post(route('room.kick', $room), ['user_id' => $other->id]);

        $room->refresh();
        $this->assertCount(1, $room->users);
        $this->assertEquals($owner->id, $room->users->first()->id);
    }

    public function test_non_owner_cannot_kick(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, [$other]);

        $this->actingAs($other)
            ->post(route('room.kick', $room), ['user_id' => $owner->id])
            ->assertStatus(403);
    }
}
