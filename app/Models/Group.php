<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('game', function (Builder $query) {
            if (auth()->hasUser()) {
                $query->whereBelongsTo(Game::current());
            }
        });
    }

    protected static function boot()
    {
        static::creating(function (Group $group) {
            $group->game_id = Game::current()->id;
        });

        parent::boot();
    }

    public function game() : BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function quizzes() : HasMany
    {
        return $this->hasMany(Quiz::class);
    }
}
