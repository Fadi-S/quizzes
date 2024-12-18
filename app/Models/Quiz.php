<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Quiz extends Model
{
    use HasRelationships;

    public function group() : BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function game() : HasOneThrough
    {
        return $this->hasOneThrough(Game::class, Group::class, 'id', 'id', 'group_id', 'game_id');
    }

    public function questions() : HasMany
    {
        return $this->hasMany(Question::class)->with("options");
    }
}
