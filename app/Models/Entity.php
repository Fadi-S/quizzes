<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Entity extends Model
{
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
