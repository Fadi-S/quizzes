<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Str;

class Entity extends Model
{
    protected static function boot()
    {
        static::addGlobalScope("game", function ($query) {
            $game = Game::current();
            if (!$game) {
                return;
            }

            $query->whereRelation("group", "game_id", $game->id);
        });

        parent::boot();
    }

    public static function getForm(): array
    {
        return [
            Forms\Components\TextInput::make("name")->label("Name")->required(),

            Forms\Components\Select::make("group_id")
                ->options(fn() => Group::pluck("name", "id"))
                ->label("Group")
                ->required(),
        ];
    }

    public static function createGuest(Group $group): self
    {
        return self::create([
            "name" => "Guest " . Str::random(6),
            "group_id" => $group->id,
        ]);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function game(): HasOneThrough
    {
        return $this->hasOneThrough(
            Game::class,
            Group::class,
            "id",
            "id",
            "group_id",
            "game_id",
        );
    }
}
