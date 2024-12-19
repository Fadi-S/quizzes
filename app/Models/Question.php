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
        "correct_answers" => "json",
        "type" => QuestionType::class,
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }

    public static function getForm(): array
    {
        return [
            Forms\Components\TextInput::make("title")->live()->required(),

            Forms\Components\TextInput::make("points")
                ->nullable()
                ->integer()
                ->minValue(0)
                ->maxValue(65535),

            Forms\Components\Radio::make("type")
                ->live()
                ->columns(3)
                ->disabled(
                    fn(?Model $record) => $record !== null && $record->exists,
                )
                ->options(QuestionType::toArray())
                ->required(),

            Forms\Components\FileUpload::make("picture")
                ->visibility("public")
                ->directory("questions")
                ->image()
                ->nullable(),

            Forms\Components\Repeater::make("options")
                ->grid()
                ->relationship()
                ->minItems(function ($get) {
                    $type = QuestionType::tryFrom($get("type"));
                    return $type === QuestionType::Choose ? 2 : 1;
                })
                ->maxItems(function ($get) {
                    $type = QuestionType::tryFrom($get("type"));
                    return $type === QuestionType::Written ? null : 6;
                })
                ->orderColumn("order")
                ->itemLabel(fn($state) => $state["name"] ?? "...")
                ->reorderableWithDragAndDrop()
                ->saveRelationshipsUsing(function (Question $record, $state) {})
                ->schema(Option::getForm()),
        ];
    }
}
