<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{

    protected $casts = [
        'correct_answers' => 'json',
        'type' => QuestionType::class,
    ];

    public function quiz() : BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options() : HasMany
    {
        return $this->hasMany(Option::class);
    }
}
