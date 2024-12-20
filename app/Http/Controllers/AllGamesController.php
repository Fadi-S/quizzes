<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameResource;
use App\Models\Game;
use Illuminate\Http\Request;

class AllGamesController extends Controller
{
    public function __invoke(Request $request)
    {
        return response()->json([
            "games" => GameResource::collection(Game::all()),
        ]);
    }
}
