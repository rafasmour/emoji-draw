<?php

namespace Tests\Feature\Room;

use App\DataObjects\RoomSettings;
use App\DataObjects\RoomStatus;
use App\DataObjects\RoomUser;
use App\Events\OwnerLeave;
use App\Events\PlayerKicked;
use App\Http\Contracts\RoomEntranceServiceInterface;
use App\Models\Room;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpKernel\Exception\HttpException;
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

    private function makeRoom(User $owner, array $extraUsers = [], int $cap = 8, array $kickedUsers = []): Room
    {
        $users = [
            ['id' => $owner->id, 'name' => $owner->name, 'score' => 0, 'guesses' => 0, 'correct_guesses' => 0, 'guessed' => false, 'room_token' => null],
        ];

        foreach ($extraUsers as $user) {
            $users[] = ['id' => $user->id, 'name' => $user->name, 'score' => 0, 'guesses' => 0, 'correct_guesses' => 0, 'guessed' => false, 'room_token' => null];
        }

        return Room::create([
            'name' => "Test Room {$owner->id}",
            'owner' => $owner->id,
            'users' => $users,
            'settings' => new RoomSettings(difficulty: 'easy', public: true, cap: $cap, rounds: 3, categories: [], language: 'en', timeLimit: 60),
            'chat' => [],
            'canvas' => [],
            'kicked_users' => $kickedUsers,
            'started' => false,
            'status' => new RoomStatus(started: false, round: 0, time: '0', term: '', guesses: 0),
        ]);
    }

    private function makePublicRoom(string $name, int $userCount = 1, int $cap = 8, bool $isPublic = true): Room
    {
        $owner = User::factory()->create();
        $extraUsers = User::factory()->count(max($userCount - 1, 0))->create()->all();

        return Room::create([
            'name' => $name,
            'owner' => $owner->id,
            'users' => collect([$owner, ...$extraUsers])->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'score' => 0,
                'guesses' => 0,
                'correct_guesses' => 0,
                'guessed' => false,
                'room_token' => null,
            ])->all(),
            'settings' => new RoomSettings(difficulty: 'easy', public: $isPublic, cap: $cap, rounds: 3, categories: [], language: 'en', timeLimit: 60),
            'chat' => [],
            'canvas' => [],
            'kicked_users' => [],
            'started' => false,
            'status' => new RoomStatus(started: false, round: 0, time: '0', term: '', guesses: 0),
        ]);
    }

    private function reloadRoom(Room $room): Room
    {
        return Room::query()->get()->firstWhere('name', $room->name)
            ?? throw new \RuntimeException('Room not found.');
    }

    public function test_user_can_join_room(): void
    {
        $owner = User::factory()->create();
        $joiner = User::factory()->create();
        $room = $this->makeRoom($owner);

        app(RoomEntranceServiceInterface::class)->join($joiner, $room);

        $room = Room::query()->firstOrFail();
        $this->assertCount(2, $room->users);
        $this->assertInstanceOf(RoomUser::class, $room->users->last());
        $this->assertEquals($joiner->id, $room->users->last()->id);
    }

    public function test_user_switching_rooms_is_removed_from_the_previous_room(): void
    {
        $ownerOne = User::factory()->create();
        $ownerTwo = User::factory()->create();
        $joiner = User::factory()->create();
        $spectator = User::factory()->create();
        $roomOne = $this->makeRoom($ownerOne, [$joiner, $spectator]);
        $roomTwo = $this->makeRoom($ownerTwo);

        app(RoomEntranceServiceInterface::class)->join($joiner, $roomTwo);

        $roomOne = $this->reloadRoom($roomOne);
        $roomTwo = $this->reloadRoom($roomTwo);

        $this->assertFalse($roomOne->users->contains('id', $joiner->id));
        $this->assertTrue($roomTwo->users->contains('id', $joiner->id));
        $this->assertCount(1, $roomOne->chat);
        $this->assertSame('Left the Room!', $roomOne->chat->last()->message);
        $this->assertCount(1, $roomTwo->chat);
        $this->assertSame('Joined the Room!', $roomTwo->chat->last()->message);
    }

    public function test_joining_same_room_is_a_no_op(): void
    {
        $owner = User::factory()->create();
        $joiner = User::factory()->create();
        $room = $this->makeRoom($owner, [$joiner]);

        app(RoomEntranceServiceInterface::class)->join($joiner, $room);

        $room = $this->reloadRoom($room);

        $this->assertCount(1, $room->users->filter(fn (RoomUser $user) => $user->id === $joiner->id));
        $this->assertCount(0, $room->chat);
    }

    public function test_user_cannot_join_full_room(): void
    {
        $owner = User::factory()->create();
        $joiner = User::factory()->create();
        $room = $this->makeRoom($owner, cap: 1);

        try {
            app(RoomEntranceServiceInterface::class)->join($joiner, $room);
            $this->fail('Expected full room join to throw.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }

        $room = $this->reloadRoom($room);
        $this->assertCount(1, $room->users);
    }

    public function test_kicked_user_cannot_join_destination_room(): void
    {
        $owner = User::factory()->create();
        $kicked = User::factory()->create();
        $room = $this->makeRoom($owner, kickedUsers: [$kicked->id]);

        try {
            app(RoomEntranceServiceInterface::class)->join($kicked, $room);
            $this->fail('Expected kicked user join to throw.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_user_can_leave_room(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $third = User::factory()->create();
        $room = $this->makeRoom($owner, [$other, $third]);

        app(RoomEntranceServiceInterface::class)->leave($other, $room);

        $room = $this->reloadRoom($room);
        $this->assertCount(2, $room->users);
        $this->assertEquals($owner->id, $room->users->first()->id);
    }

    public function test_owner_leaving_reassigns_the_room_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $third = User::factory()->create();
        $room = $this->makeRoom($owner, [$other, $third]);

        app(RoomEntranceServiceInterface::class)->leave($owner, $room);

        $room = $this->reloadRoom($room);

        $this->assertContains($room->owner, [$other->id, $third->id]);
        $this->assertNotSame($owner->id, $room->owner);
    }

    public function test_last_user_leaving_deletes_room(): void
    {
        $owner = User::factory()->create();
        $room = $this->makeRoom($owner);

        app(RoomEntranceServiceInterface::class)->leave($owner, $room);

        $this->assertNull(Room::find($room->id));
    }

    public function test_owner_can_kick_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, [$other]);

        app(RoomEntranceServiceInterface::class)->kick($owner, $room, $other->id);

        $room = $this->reloadRoom($room);
        $this->assertCount(1, $room->users);
        $this->assertEquals($owner->id, $room->users->first()->id);
        $this->assertContains($other->id, $room->kicked_users);
        $this->assertCount(1, $room->chat);
        $this->assertSame("{$owner->name} kicked {$other->name}!", $room->chat->last()->message);
    }

    public function test_non_owner_cannot_kick(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, [$other]);

        try {
            app(RoomEntranceServiceInterface::class)->kick($other, $room, $owner->id);
            $this->fail('Expected non-owner kick to throw.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_player_kicked_event_exposes_expected_payload_and_channel(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, [$other]);
        $message = "You were kicked by {$owner->name}. You can't rejoin this room.";

        $event = new PlayerKicked($other, $room, $message);
        $channels = $event->broadcastOn();

        $this->assertSame($other->id, $event->user_id);
        $this->assertSame($message, $event->message);
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame("private-room.{$room->id}", $channels[0]->name);
    }

    public function test_owner_leave_event_broadcasts_on_room_private_channel(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, [$other]);

        $channels = (new OwnerLeave($owner, $room))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame("private-room.{$room->id}", $channels[0]->name);
    }

    public function test_guest_can_view_paginated_public_rooms(): void
    {
        foreach (range(1, 12) as $index) {
            $this->makePublicRoom("Public Room {$index}", userCount: 2, cap: 8);
        }

        $this->makePublicRoom('Private Room', isPublic: false);

        $response = $this->get(route('room.rooms'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('room/index')
                ->has('rooms.data')
                ->where('rooms.current_page', 1)
                ->where('rooms.total', 12)
                ->where('rooms.data', fn ($rooms): bool => collect($rooms)->every(
                    fn (array $room): bool => ! str_contains($room['name'], 'Private')
                ))
            );

        $this->assertAuthenticated();
        $this->assertTrue((bool) auth()->user()?->is_guest);
    }

    public function test_authenticated_user_can_view_second_page_of_public_rooms(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 12) as $index) {
            $this->makePublicRoom("Public Room {$index}");
        }

        $response = $this->actingAs($user)->get(route('room.rooms', ['page' => 2]));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('room/index')
                ->where('rooms.current_page', 2)
                ->has('rooms.data')
            );
    }
}
