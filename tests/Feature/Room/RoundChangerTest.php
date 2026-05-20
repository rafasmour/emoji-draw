<?php

namespace Tests\Feature\Room;

use App\DataObjects\RoomSettings;
use App\DataObjects\RoomStatus;
use App\DataObjects\RoomUser;
use App\Http\Contracts\GameServiceInterface;
use App\Models\Room;
use App\Models\Term;
use App\Models\User;
use Tests\TestCase;

class RoundChangerTest extends TestCase
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

    private function makeRoom(User $owner, User $other): Room
    {
        return Room::create([
            'name' => 'Test Room',
            'owner' => $owner->id,
            'artist' => $owner->id,
            'users' => [
                ['id' => $owner->id, 'name' => $owner->name, 'score' => 0, 'guesses' => 0, 'correct_guesses' => 0, 'guessed' => true, 'room_token' => null],
                ['id' => $other->id, 'name' => $other->name, 'score' => 0, 'guesses' => 0, 'correct_guesses' => 0, 'guessed' => false, 'room_token' => null],
            ],
            'settings' => new RoomSettings(difficulty: 'easy', public: true, cap: 8, rounds: 3, categories: [], language: 'en', timeLimit: 60),
            'chat' => [],
            'canvas' => [['x' => 100, 'y' => 100, 'emoji' => '😀', 'size' => 10]],
            'started' => true,
            'status' => new RoomStatus(started: true, round: 1, time: '2099-01-01 00:00:00', term: 'test', guesses: 0),
        ]);
    }

    public function test_round_change_resets_all_users_guessed_flag(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);

        app(GameServiceInterface::class)->changeRound($room);

        $room->refresh();
        $room->users->each(fn (RoomUser $user) => $this->assertFalse($user->guessed));
    }

    public function test_round_change_increments_round_counter(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);

        app(GameServiceInterface::class)->changeRound($room);

        $room->refresh();
        $this->assertEquals(2, $room->status->round);
    }

    public function test_round_change_clears_canvas(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);

        app(GameServiceInterface::class)->changeRound($room);

        $room->refresh();
        $this->assertEmpty($room->canvas);
    }

    public function test_round_change_rotates_artist_away_from_previous(): void
    {
        Term::factory()->create();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);

        app(GameServiceInterface::class)->changeRound($room);

        $room->refresh();
        $this->assertEquals($other->id, $room->artist);
    }

    public function test_round_change_sets_term_from_database(): void
    {
        Term::query()->delete();
        $term = Term::factory()->create(['value' => 'unicorn']);
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);

        app(GameServiceInterface::class)->changeRound($room);

        $room->refresh();
        $this->assertEquals($term->value, $room->status->term);
    }

    public function test_round_change_stores_time_as_datetime_string(): void
    {
        Term::factory()->create();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);

        app(GameServiceInterface::class)->changeRound($room);

        $room->refresh();
        $this->assertIsString($room->status->time);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $room->status->time);
    }
}
