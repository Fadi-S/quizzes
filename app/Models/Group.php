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

    protected static function boot()
    {
        static::creating(function ($model) {
            $model->slug = str($model->name)->slug(language: null);
        });

        parent::boot();
    }

    public static function getForm(): array
    {
        return [
            Forms\Components\TextInput::make("name")
                ->required()
                ->afterStateUpdated(function (
                    string $operation,
                    $state,
                    Forms\Set $set,
                ) {
                    if ($operation === "create") {
                        $set("slug", str($state)->slug(language: null));
                    }
                })
                ->unique(
                    modifyRuleUsing: fn($rule) => $rule->where(
                        "game_id",
                        Game::current()->id,
                    ),
                ),

            Forms\Components\TextInput::make("slug")->disabled(),
        ];
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function entities(): HasMany
    {
        return $this->hasMany(Entity::class);
    }
}
