<?php

namespace Tests\Feature\Room;

use App\DataObjects\RoomSettings;
use App\DataObjects\RoomStatus;
use App\Http\Contracts\GameServiceInterface;
use App\Jobs\RoundHandler;
use App\Models\Room;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GameStateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Room::query()->delete();
        User::query()->delete();
        Term::query()->delete();
        Term::factory()->create();
    }

    protected function tearDown(): void
    {
        Room::query()->delete();
        User::query()->delete();
        Term::query()->delete();
        parent::tearDown();
    }

    private function makeRoom(User $owner, User $other, ?RoomStatus $status = null): Room
    {
        return Room::create([
            'name' => 'Test Room',
            'owner' => $owner->id,
            'artist' => $owner->id,
            'users' => [
                ['id' => $owner->id, 'name' => $owner->name, 'score' => 10, 'guesses' => 3, 'correct_guesses' => 2, 'guessed' => true, 'room_token' => null],
                ['id' => $other->id, 'name' => $other->name, 'score' => 5, 'guesses' => 2, 'correct_guesses' => 1, 'guessed' => true, 'room_token' => null],
            ],
            'settings' => new RoomSettings(difficulty: 'easy', public: true, cap: 8, rounds: 3, categories: [], language: 'en', timeLimit: 60),
            'chat' => [],
            'canvas' => [['x' => 1, 'y' => 1, 'emoji' => '😀', 'size' => 10]],
            'started' => true,
            'status' => $status ?? new RoomStatus(started: true, round: 3, time: '2026-01-01 00:01:00', term: 'cat', guesses: 2),
        ]);
    }

    public function test_finish_resets_started_to_false(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);

        app(GameServiceInterface::class)->finish($room);

        $room->refresh();
        $this->assertFalse($room->status->started);
    }

    public function test_finish_resets_round_and_time(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);

        app(GameServiceInterface::class)->finish($room);

        $room->refresh();
        $this->assertEquals(0, $room->status->round);
        $this->assertEquals('0', $room->status->time);
    }

    public function test_finish_clears_term(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);

        app(GameServiceInterface::class)->finish($room);

        $room->refresh();
        $this->assertEquals('', $room->status->term);
    }

    public function test_start_succeeds_after_finish(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);

        app(GameServiceInterface::class)->finish($room);
        $room->refresh();

        $this->actingAs($owner)
            ->postJson(route('room.start', $room))
            ->assertOk();
    }

    public function test_start_blocked_while_game_in_progress(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);

        $this->actingAs($owner)
            ->postJson(route('room.start', $room))
            ->assertStatus(403);
    }

    public function test_start_returns_json_redirect_when_expects_json(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other, new RoomStatus(started: false, round: 0, time: '0', term: '', guesses: 0));

        $response = $this->actingAs($owner)
            ->postJson(route('room.start', $room))
            ->assertOk()
            ->assertJsonStructure(['redirect']);

        $this->assertStringContainsString('/room/', $response->json('redirect'));
    }

    public function test_stale_round_handler_self_deletes(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other, new RoomStatus(started: true, round: 2, time: '2099-01-01 00:00:00', term: 'cat', guesses: 0));

        $job = new RoundHandler($room, forRound: 1);
        $job->handle(app(GameServiceInterface::class));

        $room->refresh();
        $this->assertEquals(2, $room->status->round);
        Queue::assertNothingPushed();
    }

    public function test_round_ends_early_when_all_non_artists_guessed(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = Room::create([
            'name' => 'Early Round Room',
            'owner' => $owner->id,
            'artist' => $owner->id,
            'users' => [
                ['id' => $owner->id, 'name' => $owner->name, 'score' => 0, 'guesses' => 0, 'correct_guesses' => 0, 'guessed' => false, 'room_token' => null],
                ['id' => $other->id, 'name' => $other->name, 'score' => 0, 'guesses' => 0, 'correct_guesses' => 0, 'guessed' => false, 'room_token' => null],
            ],
            'settings' => new RoomSettings(difficulty: 'easy', public: true, cap: 8, rounds: 3, categories: [], language: 'en', timeLimit: 60),
            'chat' => [],
            'canvas' => [],
            'started' => true,
            'status' => new RoomStatus(started: true, round: 1, time: '2099-01-01 00:00:00', term: 'apple', guesses: 0),
        ]);

        $this->actingAs($other)
            ->postJson(route('room.guess', $room), ['guess' => 'apple'])
            ->assertSuccessful();

        Queue::assertPushed(RoundHandler::class);
    }
}
