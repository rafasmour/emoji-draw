<?php

namespace Tests\Feature\Room;

use App\Concerns\BuildsHint;
use App\DataObjects\RoomSettings;
use App\DataObjects\RoomStatus;
use App\Events\RevealHint;
use App\Http\Controllers\Room\RoundChangerController;
use App\Jobs\HintHandler;
use App\Models\Room;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HintTest extends TestCase
{
    use BuildsHint;

    protected function setUp(): void
    {
        parent::setUp();
        Room::query()->delete();
        User::query()->delete();
        Term::query()->delete();
        Term::factory()->create(['value' => 'cat']);
    }

    protected function tearDown(): void
    {
        Room::query()->delete();
        User::query()->delete();
        Term::query()->delete();
        parent::tearDown();
    }

    private function makeRoom(User $owner, User $other, string $term = 'apple', int $round = 1): Room
    {
        return Room::create([
            'name' => 'Test Room',
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
            'status' => new RoomStatus(started: true, round: $round, time: '2099-01-01 00:00:00', term: $term, guesses: 0),
        ]);
    }

    public function test_hint_handler_broadcasts_reveal_hint_event(): void
    {
        Event::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other, 'apple', 1);

        $job = new HintHandler($room, forRound: 1);
        $job->handle();

        Event::assertDispatched(RevealHint::class);
    }

    public function test_stale_hint_handler_self_deletes(): void
    {
        Event::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other, 'apple', 2);

        $job = new HintHandler($room, forRound: 1);
        $job->handle();

        Event::assertNotDispatched(RevealHint::class);
    }

    public function test_hint_reveals_first_letter_on_first_tick(): void
    {
        Event::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other, 'apple', 1);

        $job = new HintHandler($room, forRound: 1, hintsRevealed: 0);
        $job->handle();

        Event::assertDispatched(RevealHint::class, function (RevealHint $event) {
            return $event->hint === 'a _ _ _ _';
        });
    }

    public function test_hint_reveals_letters_sequentially(): void
    {
        Event::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other, 'apple', 1);

        // hintsRevealed=1 → nextRevealed=2 → reveals 'a', 'p'
        (new HintHandler($room, forRound: 1, hintsRevealed: 1))->handle();

        Event::assertDispatched(RevealHint::class, function (RevealHint $event) {
            return $event->hint === 'a p _ _ _';
        });
    }

    public function test_hint_preserves_spaces_in_phrases(): void
    {
        Event::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other, 'fire truck', 1);

        // hintsRevealed=1 → nextRevealed=2 → 'f i'; trailing space + double space = 3-space gap
        (new HintHandler($room, forRound: 1, hintsRevealed: 1))->handle();

        Event::assertDispatched(RevealHint::class, function (RevealHint $event) {
            return $event->hint === 'f i _ _   _ _ _ _ _';
        });
    }

    public function test_initial_hint_is_all_underscores(): void
    {
        $this->assertEquals('_ _ _ _ _', $this->buildHint('apple', 0));
        // trailing space on each letter + double space word gap = 3 spaces between words
        $this->assertEquals('_ _ _ _   _ _ _ _ _', $this->buildHint('fire truck', 0));
    }

    public function test_hint_handler_stops_dispatching_after_all_letters_revealed(): void
    {
        Queue::fake();
        Event::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other, 'cat', 1);

        // 'cat' has 3 letters; hintsRevealed=2 means next reveal is 3 (the last)
        (new HintHandler($room, forRound: 1, hintsRevealed: 2))->handle();

        Queue::assertNotPushed(HintHandler::class);
    }

    public function test_hint_handler_dispatches_next_when_letters_remain(): void
    {
        Queue::fake();
        Event::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other, 'apple', 1);

        (new HintHandler($room, forRound: 1, hintsRevealed: 0))->handle();

        Queue::assertPushed(HintHandler::class);
    }

    public function test_round_change_dispatches_hint_handler(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = Room::create([
            'name' => 'Test Room',
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
            'status' => new RoomStatus(started: true, round: 0, time: '2099-01-01 00:00:00', term: '', guesses: 0),
        ]);

        (new RoundChangerController)->change($room);

        Queue::assertPushed(HintHandler::class);
    }
}
