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

    protected static function boot()
    {
        static::creating(function (Question $model) {
            if($model->type === QuestionType::Written) {
                $model->correct_answers = [];
            }
        });

        static::updating(function (Question $model) {
            if($model->type === QuestionType::Written) {
                $model->correct_answers = [];
            }
        });

        parent::boot();
    }

    public function quiz() : BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options() : HasMany
    {
        return $this->hasMany(Option::class);
    }
}
