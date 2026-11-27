<?php

namespace Tests\Feature\Room;

use App\DataObjects\RoomSettings;
use App\DataObjects\RoomStatus;
use App\Events\ArtistTermAssigned;
use App\Events\StartRound;
use App\Http\Contracts\GameServiceInterface;
use App\Models\Room;
use App\Models\Term;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ArtistTermSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Room::query()->delete();
        User::query()->delete();
        Term::query()->delete();
    }

    protected function tearDown(): void
    {
        Room::query()->delete();
        User::query()->delete();
        Term::query()->delete();
        parent::tearDown();
    }

    /** The log broadcaster authorizes every channel; switch to reverb so channel callbacks run. */
    private function useAuthorizingBroadcaster(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);

        require base_path('routes/channels.php');
    }

    private function makeRoom(User $owner, User $other, bool $started = true): Room
    {
        return Room::create([
            'name' => 'Secure Room',
            'owner' => $owner->id,
            'artist' => $owner->id,
            'users' => [
                ['id' => $owner->id, 'name' => $owner->name, 'score' => 0, 'guesses' => 0, 'correct_guesses' => 0, 'guessed' => false, 'room_token' => null],
                ['id' => $other->id, 'name' => $other->name, 'score' => 0, 'guesses' => 0, 'correct_guesses' => 0, 'guessed' => false, 'room_token' => null],
            ],
            'settings' => new RoomSettings(difficulty: 'easy', public: true, cap: 8, rounds: 3, categories: [], language: 'en', timeLimit: 60),
            'chat' => [],
            'canvas' => [],
            'kicked_users' => [],
            'status' => new RoomStatus(
                started: $started,
                round: $started ? 1 : 0,
                time: $started ? now()->addMinute()->toDateTimeString('second') : '0',
                term: $started ? 'unicorn' : '',
                guesses: 0,
            ),
        ]);
    }

    public function test_start_round_payload_does_not_expose_term(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);
        $event = new StartRound($room);

        $this->assertObjectNotHasProperty('term', $event);
        $this->assertSame($room->artist, $event->artist_id);
        $this->assertSame(1, $event->round);
        $this->assertSame('_ _ _ _ _ _ _', $event->initial_hint);
    }

    public function test_artist_term_broadcasts_only_on_artist_user_room_channel(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);
        $event = new ArtistTermAssigned($room);
        $channel = $event->broadcastOn();

        $this->assertInstanceOf(PrivateChannel::class, $channel);
        $this->assertSame("private-room.{$room->id}.user.{$owner->id}", $channel->name);
        $this->assertSame('unicorn', $event->term);
        $this->assertSame(1, $event->round);
    }

    public function test_user_can_authorize_own_room_channel_but_not_another_users_channel(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);
        $this->useAuthorizingBroadcaster();

        $this->actingAs($owner)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => "private-room.{$room->id}.user.{$owner->id}",
            ])
            ->assertOk();

        $this->actingAs($other)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => "private-room.{$room->id}.user.{$owner->id}",
            ])
            ->assertForbidden();
    }

    public function test_game_view_exposes_term_only_to_artist(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other);

        $this->actingAs($owner)
            ->get(route('room.game', $room))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('is_artist', true)
                ->where('room.status.term', 'unicorn'));

        $this->actingAs($other)
            ->get(route('room.game', $room))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('is_artist', false)
                ->where('room.status.term', null));
    }

    public function test_game_start_assigns_database_term_and_broadcasts_both_round_events(): void
    {
        Queue::fake();
        Event::fake([StartRound::class, ArtistTermAssigned::class]);
        Term::factory()->create(['value' => 'pineapple']);
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, $other, started: false);

        app(GameServiceInterface::class)->start($owner, $room);

        $room->refresh();
        $this->assertSame('pineapple', $room->status->term);
        $this->assertNotSame('test', $room->status->term);
        Event::assertDispatched(StartRound::class);
        Event::assertDispatched(ArtistTermAssigned::class);
    }
}
