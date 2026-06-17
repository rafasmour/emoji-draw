<?php

namespace Tests\Feature\Room;

use App\DataObjects\RoomSettings;
use App\DataObjects\RoomStatus;
use App\Events\StopRound;
use App\Http\Contracts\GameActionServiceInterface;
use App\Http\Contracts\GameServiceInterface;
use App\Jobs\RoundHandler;
use App\Models\Room;
use App\Models\Term;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class GameStateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Room::query()->delete();
        User::query()->delete();
        Term::query()->delete();
        Term::factory()->create(['value' => 'apple']);
        Carbon::setTestNow('2026-01-01 00:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Room::query()->delete();
        User::query()->delete();
        Term::query()->delete();
        parent::tearDown();
    }

    private function createGameRoom(User $owner, array $guessers = [], ?RoomStatus $status = null): Room
    {
        $users = [[
            'id' => $owner->id,
            'name' => $owner->name,
            'score' => 0,
            'guesses' => 0,
            'correct_guesses' => 0,
            'guessed' => false,
            'room_token' => null,
        ]];

        foreach ($guessers as $guesser) {
            $users[] = [
                'id' => $guesser->id,
                'name' => $guesser->name,
                'score' => 0,
                'guesses' => 0,
                'correct_guesses' => 0,
                'guessed' => false,
                'room_token' => null,
            ];
        }

        return Room::create([
            'name' => "Test Room {$owner->id}",
            'owner' => $owner->id,
            'artist' => $owner->id,
            'users' => $users,
            'settings' => new RoomSettings(difficulty: 'easy', public: true, cap: 8, rounds: 3, categories: [], language: 'en', timeLimit: 60),
            'chat' => [],
            'canvas' => [],
            'kicked_users' => [],
            'status' => $status ?? new RoomStatus(
                started: true,
                round: 1,
                time: Carbon::now()->addSeconds(60)->toDateTimeString('second'),
                term: 'apple',
                guesses: 0,
            ),
        ]);
    }

    private function reloadRoom(Room $room): Room
    {
        return Room::query()->firstOrFail();
    }

    public function test_start_resets_players_and_dispatches_one_round_handler(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->createGameRoom(
            $owner,
            [$other],
            new RoomStatus(started: false, round: 0, time: '0', term: '', guesses: 0),
        );

        $room->users = $room->users->map(fn ($user, $index) => new \App\DataObjects\RoomUser(
            id: $user->id,
            name: $user->name,
            score: $index === 0 ? 20 : 10,
            guesses: 2,
            correct_guesses: 1,
            guessed: true,
            room_token: null,
        ));
        $room->save();

        app(GameServiceInterface::class)->start($owner, $room);

        $room = $this->reloadRoom($room);

        $this->assertTrue($room->status->started);
        $this->assertCount(1, $room->chat);
        $this->assertSame('started game', $room->chat->last()->message);
        $room->users->each(function ($user): void {
            $this->assertSame(0, $user->score);
            $this->assertSame(0, $user->guesses);
            $this->assertSame(0, $user->correct_guesses);
            $this->assertFalse($user->guessed);
        });

        Queue::assertPushed(RoundHandler::class, 1);
    }

    public function test_start_returns_forbidden_for_non_owner(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->createGameRoom(
            $owner,
            [$other],
            new RoomStatus(started: false, round: 0, time: '0', term: '', guesses: 0),
        );

        try {
            app(GameServiceInterface::class)->start($other, $room);
            $this->fail('Expected non-owner start to throw.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        Queue::assertNothingPushed();
    }

    public function test_finish_resets_game_state(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->createGameRoom($owner, [$other]);

        app(GameServiceInterface::class)->finish($room);

        $room = $this->reloadRoom($room);
        $this->assertFalse($room->status->started);
        $this->assertSame(0, $room->status->round);
        $this->assertSame('0', $room->status->time);
        $this->assertSame('', $room->status->term);
    }

    public function test_correct_guess_updates_scores_and_state_once(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $guesser = User::factory()->create();
        $room = $this->createGameRoom($owner, [$guesser]);

        app(GameActionServiceInterface::class)->handleGuess($guesser, $room, 'apple');

        $room = $this->reloadRoom($room);
        $guesserStats = $room->users->firstWhere('id', $guesser->id);
        $artistStats = $room->users->firstWhere('id', $owner->id);

        $this->assertSame(600, $guesserStats->score);
        $this->assertSame(1, $guesserStats->correct_guesses);
        $this->assertTrue($guesserStats->guessed);
        $this->assertSame(300, $artistStats->score);
        $this->assertSame(1, $room->status->guesses);
        $this->assertCount(1, $room->chat);
        $this->assertSame('Guessed Correctly!', $room->chat->last()->message);

        Queue::assertPushed(RoundHandler::class, 1);
    }

    public function test_incorrect_guess_adds_one_chat_message_and_no_points(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $guesser = User::factory()->create();
        $room = $this->createGameRoom($owner, [$guesser]);

        app(GameActionServiceInterface::class)->handleGuess($guesser, $room, 'wrong');

        $room = $this->reloadRoom($room);
        $guesserStats = $room->users->firstWhere('id', $guesser->id);
        $artistStats = $room->users->firstWhere('id', $owner->id);

        $this->assertSame(0, $guesserStats->score);
        $this->assertSame(1, $guesserStats->guesses);
        $this->assertSame(0, $artistStats->score);
        $this->assertSame(0, $room->status->guesses);
        $this->assertCount(1, $room->chat);
        $this->assertSame('wrong', $room->chat->last()->message);

        Queue::assertNothingPushed();
    }

    public function test_second_correct_guesser_does_not_get_first_guess_bonus(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $firstGuesser = User::factory()->create();
        $secondGuesser = User::factory()->create();
        $room = $this->createGameRoom($owner, [$firstGuesser, $secondGuesser]);

        app(GameActionServiceInterface::class)->handleGuess($firstGuesser, $room, 'apple');

        $room = $this->reloadRoom($room);
        $firstScore = $room->users->firstWhere('id', $firstGuesser->id)->score;

        app(GameActionServiceInterface::class)->handleGuess($secondGuesser, $room, 'apple');

        $room = $this->reloadRoom($room);
        $secondScore = $room->users->firstWhere('id', $secondGuesser->id)->score;

        $this->assertSame(600, $firstScore);
        $this->assertSame(500, $secondScore);
    }

    public function test_repeat_guess_returns_forbidden_without_extra_mutation(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $guesser = User::factory()->create();
        $room = $this->createGameRoom($owner, [$guesser]);

        app(GameActionServiceInterface::class)->handleGuess($guesser, $room, 'apple');

        try {
            app(GameActionServiceInterface::class)->handleGuess($guesser, $room, 'apple');
            $this->fail('Expected repeated guess to throw.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $room = $this->reloadRoom($room);
        $guesserStats = $room->users->firstWhere('id', $guesser->id);

        $this->assertSame(600, $guesserStats->score);
        $this->assertSame(1, $guesserStats->correct_guesses);
        $this->assertCount(1, $room->chat);
    }

    public function test_artist_cannot_guess(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->createGameRoom($owner, [$other]);

        try {
            app(GameActionServiceInterface::class)->handleGuess($owner, $room, 'apple');
            $this->fail('Expected artist guess to throw.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $room = $this->reloadRoom($room);
        $this->assertCount(0, $room->chat);
        $this->assertSame(0, $room->status->guesses);
        Queue::assertNothingPushed();
    }

    public function test_all_non_artists_guessing_correctly_queues_one_round_transition_each(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $firstGuesser = User::factory()->create();
        $secondGuesser = User::factory()->create();
        $room = $this->createGameRoom($owner, [$firstGuesser, $secondGuesser]);

        app(GameActionServiceInterface::class)->handleGuess($firstGuesser, $room, 'apple');

        Queue::assertNothingPushed();

        app(GameActionServiceInterface::class)->handleGuess($secondGuesser, $room, 'apple');

        Queue::assertPushed(RoundHandler::class, 1);
    }

    public function test_stale_round_handler_self_deletes(): void
    {
        Queue::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->createGameRoom(
            $owner,
            [$other],
            new RoomStatus(started: true, round: 2, time: '2099-01-01 00:00:00', term: 'cat', guesses: 0),
        );

        $job = new RoundHandler($room, forRound: 1);
        $job->handle(app(GameServiceInterface::class));

        $room = $this->reloadRoom($room);
        $this->assertSame(2, $room->status->round);
        Queue::assertNothingPushed();
    }

    public function test_stop_round_broadcasts_on_room_private_channel(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->createGameRoom($owner, [$other]);

        $channels = (new StopRound($room))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame("private-room.{$room->id}", $channels[0]->name);
    }
}
