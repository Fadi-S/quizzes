<?php

namespace App\Models;

use App\Traits\BelongsToGame;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Forms;

class Group extends Model
{
    use BelongsToGame;

    protected $casts = [
        "data" => "json",
    ];

    public static function getForm(): array
    {
        return [
            Forms\Components\TextInput::make("name")
                ->required()
                ->unique(
                    modifyRuleUsing: fn($rule) => $rule->where(
                        "game_id",
                        Game::current()->id,
                    ),
                ),
        ];
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }
}
