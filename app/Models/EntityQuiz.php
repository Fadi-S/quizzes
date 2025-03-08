<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class EntityQuiz extends Model
{
    protected $guarded = [];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(EntityQuestion::class, "entity_quiz_id");
        //        return $this->hasManyThrough(
        //            EntityQuestion::class,
        //            Entity::class,
        //            "id",
        //            "entity_id",
        //            "entity_id",
        //            "id",
        //        );
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
