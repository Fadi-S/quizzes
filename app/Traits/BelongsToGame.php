<?php

namespace App\Traits;

use App\Models\Game;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToGame
{
    protected static function bootBelongsToGame()
    {
        static::addGlobalScope("game", function ($query) {
            $game = Game::current();
            if (!$game) {
                return;
            }

            $query->where("game_id", $game->id);
        });

        static::creating(function ($model) {
            $game = Game::current();

            if (!$game) {
                return;
            }

            $model->game_id = $game->id;
        });
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
