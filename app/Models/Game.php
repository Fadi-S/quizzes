<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
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

    public function getRouteKeyName()
    {
        return "slug";
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function entities(): HasManyThrough
    {
        return $this->hasManyThrough(Entity::class, Group::class);
    }

    public function quizzes(): HasManyThrough
    {
        return $this->hasManyThrough(Quiz::class, Group::class);
    }

    public function createAPIKey(): array
    {
        $key = ApiKey::generate();
        $secret = ApiKey::generate(64);

        $this->apiKeys()->create([
            "key" => $key,
            "secret" => Hash::make($secret),
        ]);

        return [
            "key" => $key,
            "secret" => $secret,
        ];
    }

    public static function current(): ?self
    {
        $id = auth()->id();
        $currentGame = Filament::getTenant();

        if (!$id || !$currentGame instanceof self) {
            return null;
        }

        return $currentGame;
    }
}
