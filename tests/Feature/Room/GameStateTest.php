<?php

namespace Tests\Feature\Room;

use App\DataObjects\RoomSettings;
use App\Http\Controllers\Room\GameStateController;
use App\Models\Room;
use App\Models\Term;
use App\Models\User;
use Illuminate\Http\Request;
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

    private function makeRoom(User $owner, User $other, array $statusOverrides = []): Room
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
            'status' => array_merge(['started' => true, 'round' => 3, 'time' => '2026-01-01 00:01:00', 'term' => 'cat', 'guesses' => 2], $statusOverrides),
        ]);
    }

    public function test_finish_resets_started_to_false(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);

        (new GameStateController)->finish($room);

        $room->refresh();
        $this->assertFalse($room->status['started']);
    }

    public function test_finish_resets_round_and_time(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);

        (new GameStateController)->finish($room);

        $room->refresh();
        $this->assertEquals(0, $room->status['round']);
        $this->assertEquals(0, $room->status['time']);
    }

    public function test_finish_clears_term(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);

        (new GameStateController)->finish($room);

        $room->refresh();
        $this->assertEquals('', $room->status['term']);
    }

    public function test_start_succeeds_after_finish(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);

        (new GameStateController)->finish($room);
        $room->refresh();

        $request = Request::create('/room/start', 'POST');
        $request->setUserResolver(fn () => $owner);

        $response = (new GameStateController)->start($request, $room);

        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_start_blocked_while_game_in_progress(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);

        $request = Request::create('/room/start', 'POST');
        $request->setUserResolver(fn () => $owner);

        $response = (new GameStateController)->start($request, $room);

        $this->assertEquals(403, $response->getStatusCode());
    }
}
