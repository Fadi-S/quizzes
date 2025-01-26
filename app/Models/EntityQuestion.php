<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityQuestion extends Model
{
    protected $table = "entity_question";

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class)->withPivot(
            "answer",
            "points",
            "is_correct",
        );
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}
