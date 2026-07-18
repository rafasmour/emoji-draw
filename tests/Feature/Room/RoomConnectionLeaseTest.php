<?php

namespace Tests\Feature\Room;

use App\DataObjects\RoomSettings;
use App\DataObjects\RoomStatus;
use App\Http\Contracts\RoomConnectionLeaseServiceInterface;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RoomConnectionLeaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.stores.mongodb', [
            'driver' => 'array',
            'serialize' => false,
        ]);
        config()->set('cache.stores.mongodb_room_leases', [
            'driver' => 'array',
            'serialize' => false,
        ]);

        config()->set('room.disconnect_grace_seconds', 30);
        config()->set('room.heartbeat_interval_seconds', 10);

        Room::query()->delete();
        User::query()->delete();
        Cache::store('mongodb_room_leases')->clear();
    }

    protected function tearDown(): void
    {
        Cache::store('mongodb_room_leases')->clear();
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
            'name' => "Lease Room {$owner->id}",
            'owner' => $owner->id,
            'users' => $users,
            'settings' => new RoomSettings(difficulty: 'easy', public: true, cap: 8, rounds: 3, categories: [], language: 'en', timeLimit: 60),
            'chat' => [],
            'canvas' => [],
            'kicked_users' => [],
            'started' => false,
            'status' => new RoomStatus(started: false, round: 0, time: '0', term: '', guesses: 0),
        ]);
    }

    private function reloadRoom(Room $room): ?Room
    {
        return Room::query()->find($room->getKey());
    }

    public function test_room_member_can_send_connection_heartbeat(): void
    {
        $owner = User::factory()->create();
        $room = $this->makeRoom($owner);

        $response = $this
            ->actingAs($owner)
            ->postJson(route('room.connection.heartbeat', $room), [
                'tab_id' => 'tab-alpha',
            ]);

        $response->assertNoContent();

        $leaseService = app(RoomConnectionLeaseServiceInterface::class);

        $this->assertTrue($leaseService->hasFreshTabs($room, $owner->getKey()));
        $this->assertSame(['tab-alpha'], $leaseService->freshTabs($room, $owner->getKey()));
    }

    public function test_room_heartbeat_requires_tab_id(): void
    {
        $owner = User::factory()->create();
        $room = $this->makeRoom($owner);

        $this
            ->actingAs($owner)
            ->postJson(route('room.connection.heartbeat', $room), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tab_id']);
    }

    public function test_prune_command_removes_user_with_expired_lease(): void
    {
        $owner = User::factory()->create();
        $player = User::factory()->create();
        $room = $this->makeRoom($owner, [$player]);

        app(RoomConnectionLeaseServiceInterface::class)->touch($room, $owner, 'owner-tab');

        $this->artisan('rooms:prune-disconnected')->assertExitCode(0);

        $room = $this->reloadRoom($room);

        $this->assertNotNull($room);
        $this->assertTrue($room->users->contains('id', $owner->id));
        $this->assertFalse($room->users->contains('id', $player->id));
    }

    public function test_prune_command_keeps_user_with_fresh_lease(): void
    {
        $owner = User::factory()->create();
        $room = $this->makeRoom($owner);

        app(RoomConnectionLeaseServiceInterface::class)->touch($room, $owner, 'owner-tab');

        $this->artisan('rooms:prune-disconnected')->assertExitCode(0);

        $room = $this->reloadRoom($room);

        $this->assertNotNull($room);
        $this->assertTrue($room->users->contains('id', $owner->id));
    }

    public function test_user_stays_in_room_until_all_tabs_are_stale(): void
    {
        $baseTime = now();
        $owner = User::factory()->create();
        $player = User::factory()->create();
        $room = $this->makeRoom($owner, [$player]);

        app(RoomConnectionLeaseServiceInterface::class)->touch($room, $owner, 'owner-tab', $baseTime);

        $leaseService = app(RoomConnectionLeaseServiceInterface::class);

        $leaseService->touch($room, $player, 'stale-tab', $baseTime->copy()->subSeconds(31));
        $leaseService->touch($room, $player, 'fresh-tab', $baseTime);

        $this->travelTo($baseTime);
        $this->artisan('rooms:prune-disconnected')->assertExitCode(0);

        $room = $this->reloadRoom($room);

        $this->assertNotNull($room);
        $this->assertTrue($room->users->contains('id', $player->id));

        $this->travelTo($baseTime->copy()->addSeconds(31));
        $leaseService->touch($room, $owner, 'owner-tab', $baseTime->copy()->addSeconds(31));
        $this->artisan('rooms:prune-disconnected')->assertExitCode(0);

        $room = $this->reloadRoom($room);

        $this->assertNotNull($room);
        $this->assertFalse($room->users->contains('id', $player->id));
    }

    public function test_pruning_owner_triggers_owner_reassignment(): void
    {
        $owner = User::factory()->create();
        $nextOwner = User::factory()->create();
        $room = $this->makeRoom($owner, [$nextOwner]);

        app(RoomConnectionLeaseServiceInterface::class)->touch($room, $nextOwner, 'next-owner-tab');

        $this->artisan('rooms:prune-disconnected')->assertExitCode(0);

        $room = $this->reloadRoom($room);

        $this->assertNotNull($room);
        $this->assertSame($nextOwner->id, $room->owner);
        $this->assertTrue($room->users->contains('id', $nextOwner->id));
        $this->assertFalse($room->users->contains('id', $owner->id));
    }

    public function test_pruning_final_user_deletes_room(): void
    {
        $owner = User::factory()->create();
        $room = $this->makeRoom($owner);

        $this->artisan('rooms:prune-disconnected')->assertExitCode(0);

        $this->assertNull($this->reloadRoom($room));
    }
}
