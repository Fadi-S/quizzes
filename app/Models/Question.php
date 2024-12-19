<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Forms;

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

    public static function getForm() : array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->live()
                ->required(),

            Forms\Components\Radio::make('type')
                ->live()
                ->columns(3)
                ->disabled(fn(?Model $record) => $record !== null && $record->exists)
                ->options(QuestionType::toArray())
                ->required(),

            Forms\Components\FileUpload::make('picture')
                ->visibility('public')
                ->image()
                ->nullable(),

            Forms\Components\Repeater::make('options')
                ->grid()
                ->relationship()
                ->minItems(function ($get) {
                    $type = QuestionType::tryFrom($get('type'));
                    if ($type === QuestionType::Choose) {
                        return 2;
                    }

                    return 1;
                })
                ->maxItems(function ($get) {
                    $type = QuestionType::tryFrom($get('type'));

                    if ($type === QuestionType::Written) {
                        return null;
                    }

                    return 6;
                })
                ->orderColumn('order')
                ->itemLabel(fn($state) => $state['name'] ?? '...')
                ->reorderableWithDragAndDrop()
                ->schema(Option::getForm()),
        ];
    }
}
