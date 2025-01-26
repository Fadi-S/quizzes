<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityQuestion extends Model
{
    protected $table = "entity_question";

    protected $casts = [
        "answer" => "json",
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }
}
