<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class Game extends Model
{
    protected static function boot()
    {
        static::creating(function (Game $game) {
            $game->slug = str($game->name)->slug(language: null);
        });

        parent::boot();
    }

    public function apiKeys() : HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function createAPIKey(): array
    {
        $key = ApiKey::generate();
        $secret = ApiKey::generate();

        $this->apiKeys()->create([
            'key' => $key,
            'secret' => Hash::make($secret),
        ]);

        return [
            'key' => $key,
            'secret' => $secret,
        ];
    }

    public static function current() : ?self
    {
        $id = auth()->id();
        $currentGameId = session('current_game_id');

        if(!$id || !$currentGameId) {
            return null;
        }

        return Cache::remember(
            "game_$currentGameId",
            now()->addMinutes(5),
            fn() => static::find($currentGameId)
        );
    }
}
