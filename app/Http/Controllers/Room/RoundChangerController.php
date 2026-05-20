<?php

namespace App\Http\Controllers\Room;

use App\Http\Contracts\GameServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Room;

class RoundChangerController extends Controller
{
    public function __construct(
        private GameServiceInterface $gameService,
    ) {}

    public function change(Room $room): void
    {
        $this->gameService->changeRound($room);
    }
}
