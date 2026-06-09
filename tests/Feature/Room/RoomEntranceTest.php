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
            'kicked_users' => [],
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

    public function test_kick_adds_user_to_kicked_users(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, [$other]);

        $this->actingAs($owner)
            ->post(route('room.kick', $room), ['user_id' => $other->id]);

        $room->refresh();
        $this->assertContains($other->id, $room->kicked_users);
    }

    public function test_kicked_user_cannot_rejoin(): void
    {
        $owner = User::factory()->create();
        $kicked = User::factory()->create();
        $room = $this->makeRoom($owner, [$kicked]);

        $this->actingAs($owner)
            ->post(route('room.kick', $room), ['user_id' => $kicked->id]);

        $this->actingAs($kicked)
            ->post(route('room.join'), ['room_id' => $room->id])
            ->assertStatus(403);
    }

    public function test_non_kicked_user_can_join(): void
    {
        $owner = User::factory()->create();
        $joiner = User::factory()->create();
        $kicked = User::factory()->create();
        $room = $this->makeRoom($owner, [$kicked]);

        $this->actingAs($owner)
            ->post(route('room.kick', $room), ['user_id' => $kicked->id]);

        $this->actingAs($joiner)
            ->post(route('room.join'), ['room_id' => $room->id])
            ->assertRedirect(route('room.lobby', $room));
    }

    public function test_game_finishes_early_if_only_one_player_remains_after_kicking(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, [$other]);

        // Simulate game started
        $room->status = new RoomStatus(started: true, round: 1, time: '60', term: 'apple', guesses: 0);
        $room->save();

        $this->actingAs($owner)
            ->post(route('room.kick', $room), ['user_id' => $other->id]);

        $room->refresh();
        $this->assertFalse($room->status->started);
        $this->assertEquals(0, $room->status->round);
    }

    public function test_game_finishes_early_if_only_one_player_remains_after_leaving(): void
    {
        $owner = User::factory()->create();
        $other1 = User::factory()->create();
        $other2 = User::factory()->create();
        $other3 = User::factory()->create();
        $room = $this->makeRoom($owner, [$other1, $other2, $other3]);

        // Simulate game started
        $room->status = new RoomStatus(started: true, round: 1, time: '60', term: 'apple', guesses: 0);
        $room->save();

        // One player leaves, 3 left.
        $this->actingAs($other1)
            ->post(route('room.leave', $room));

        $room->refresh();
        $this->assertTrue($room->status->started);
        $this->assertCount(3, $room->users);

        // Another player leaves, 2 left.
        $this->actingAs($other2)
            ->post(route('room.leave', $room));

        $room->refresh();
        $this->assertTrue($room->status->started);
        $this->assertCount(2, $room->users);

        // Another player leaves, 1 left.
        $this->actingAs($other3)
            ->post(route('room.leave', $room));

        $this->assertNotNull($room->fresh());
        $room->refresh();
        $this->assertFalse($room->status->started);
        $this->assertEquals(0, $room->status->round);
        $this->assertCount(1, $room->users);
    }

    public function test_user_is_kicked_from_other_rooms_when_joining_new_room(): void
    {
        $user = User::factory()->create();
        $owner1 = User::factory()->create();
        $owner2 = User::factory()->create();

        $room1 = $this->makeRoom($owner1, [$user]);
        $room2 = $this->makeRoom($owner2);

        $this->actingAs($user)
            ->post(route('room.join'), ['room_id' => $room2->id])
            ->assertRedirect(route('room.lobby', $room2));

        $this->assertFalse($room1->fresh()->users->contains('id', $user->id));
        $this->assertTrue($room2->fresh()->users->contains('id', $user->id));
    }
}
