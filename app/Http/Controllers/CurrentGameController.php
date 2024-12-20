<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResource;
use App\Models\Game;
use Illuminate\Http\Request;

class CurrentGameController extends Controller
{
    public function __invoke()
    {
        return response()->json([
            "game" => GameResource::make(Game::current()),
        ]);
    }
}
