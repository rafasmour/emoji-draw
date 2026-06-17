<?php

namespace Tests\Feature\Room;

use App\DataObjects\RoomSettings;
use App\DataObjects\RoomStatus;
use App\Http\Contracts\RoomSettingsServiceInterface;
use App\Http\Requests\Room\UpdateRoomSettingsRequest;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RoomSettingsTest extends TestCase
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

    private function makeRoom(User $owner, array $extraUsers = []): Room
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

        foreach ($extraUsers as $user) {
            $users[] = [
                'id' => $user->id,
                'name' => $user->name,
                'score' => 0,
                'guesses' => 0,
                'correct_guesses' => 0,
                'guessed' => false,
                'room_token' => null,
            ];
        }

        return Room::create([
            'name' => 'Settings Room',
            'owner' => $owner->id,
            'users' => $users,
            'settings' => new RoomSettings(difficulty: 'easy', public: true, cap: 8, rounds: 3, categories: [], language: 'en', timeLimit: 60),
            'chat' => [],
            'canvas' => [],
            'kicked_users' => [],
            'status' => new RoomStatus(started: false, round: 0, time: '0', term: '', guesses: 0),
        ]);
    }

    public function test_owner_can_update_room_settings(): void
    {
        $owner = User::factory()->create();
        $room = $this->makeRoom($owner);

        $settings = app(RoomSettingsServiceInterface::class)->update($owner, $room, [
            'cap' => 6,
            'public' => false,
            'timeLimit' => 120,
            'difficulty' => 'medium',
            'rounds' => 5,
        ]);

        $this->assertSame(6, $settings->cap);
        $this->assertFalse($settings->public);
        $this->assertSame(120, $settings->timeLimit);
        $this->assertSame('medium', $settings->difficulty);
        $this->assertSame(5, $settings->rounds);
    }

    public function test_non_owner_cannot_update_room_settings(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $room = $this->makeRoom($owner, [$other]);

        try {
            app(RoomSettingsServiceInterface::class)->update($other, $room, ['cap' => 6]);
            $this->fail('Expected unauthorized room settings update to throw.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_partial_updates_preserve_existing_settings(): void
    {
        $owner = User::factory()->create();
        $room = $this->makeRoom($owner);

        $settings = app(RoomSettingsServiceInterface::class)->update($owner, $room, ['cap' => 12]);

        $this->assertSame(12, $settings->cap);
        $this->assertTrue($settings->public);
        $this->assertSame(60, $settings->timeLimit);
        $this->assertSame('easy', $settings->difficulty);
    }

    public function test_time_limit_must_be_an_allowed_value(): void
    {
        $validator = Validator::make(['timeLimit' => 45], (new UpdateRoomSettingsRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('timeLimit', $validator->errors()->toArray());
    }

    public function test_difficulty_must_be_a_string(): void
    {
        $validator = Validator::make(['difficulty' => ['easy']], (new UpdateRoomSettingsRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('difficulty', $validator->errors()->toArray());
    }

    public function test_difficulty_must_be_an_allowed_value(): void
    {
        $validator = Validator::make(['difficulty' => 'expert'], (new UpdateRoomSettingsRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('difficulty', $validator->errors()->toArray());
    }

    public function test_cap_and_rounds_still_validate_bounds(): void
    {
        $validator = Validator::make([
            'cap' => 0,
            'rounds' => 11,
        ], (new UpdateRoomSettingsRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('cap', $validator->errors()->toArray());
        $this->assertArrayHasKey('rounds', $validator->errors()->toArray());
    }
}
