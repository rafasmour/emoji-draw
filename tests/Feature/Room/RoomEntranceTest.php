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

    public function test_joining_second_room_removes_user_from_first(): void
    {
        $owner1 = User::factory()->create();
        $owner2 = User::factory()->create();
        $joiner = User::factory()->create();
        $room1 = $this->makeRoom($owner1, [$joiner]);
        $room2 = $this->makeRoom($owner2);

        $this->actingAs($joiner)
            ->post(route('room.join'), ['room_id' => $room2->id])
            ->assertRedirect(route('room.lobby', $room2));

        $room1->refresh();
        $room2->refresh();
        $this->assertFalse($room1->users->contains('id', $joiner->id));
        $this->assertTrue($room2->users->contains('id', $joiner->id));
    }

    public function test_joining_same_room_twice_does_not_duplicate_user(): void
    {
        $owner = User::factory()->create();
        $joiner = User::factory()->create();
        $room = $this->makeRoom($owner, [$joiner]);

        $this->actingAs($joiner)
            ->post(route('room.join'), ['room_id' => $room->id])
            ->assertRedirect(route('room.lobby', $room));

        $room->refresh();
        $this->assertCount(1, $room->users->filter(fn (RoomUser $u) => $u->id === $joiner->id));
    }

    public function test_user_in_only_one_room_at_a_time(): void
    {
        $owner1 = User::factory()->create();
        $owner2 = User::factory()->create();
        $owner3 = User::factory()->create();
        $joiner = User::factory()->create();
        $room1 = $this->makeRoom($owner1, [$joiner]);
        $room2 = $this->makeRoom($owner2);
        $room3 = $this->makeRoom($owner3);

        $this->actingAs($joiner)->post(route('room.join'), ['room_id' => $room2->id]);
        $this->actingAs($joiner)->post(route('room.join'), ['room_id' => $room3->id]);

        $room1->refresh();
        $room2->refresh();
        $room3->refresh();
        $this->assertFalse($room1->users->contains('id', $joiner->id));
        $this->assertFalse($room2->users->contains('id', $joiner->id));
        $this->assertTrue($room3->users->contains('id', $joiner->id));
    }
}
