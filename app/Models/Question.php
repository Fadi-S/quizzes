<?php

namespace App\Models;

use App\Enums\QuestionType;
use App\Traits\Linkable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Forms;
use Illuminate\Support\Collection;

class Question extends Model
{
    use Linkable;

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
        return $this->hasMany(Option::class)->orderBy("order");
    }

    public function check(string|int|array $answer): bool
    {
        return $this->type->getChecker()->check($this, $answer);
    }

    public function getAnswers(): array|int|string
    {
        return $this->type->getChecker()->getCorrectAnswer($this);
    }

    public static function calculateCorrectAnswers(
        QuestionType $type,
        array $options,
    ): Collection {
        $correctAnswers = collect();
        $order = 1;
        foreach ($options as $option) {
            if ($option["is_correct"]) {
                $correctAnswers->push($order);
            }

            $order++;
        }

        return $correctAnswers;
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
                ->visibility("private")
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
                ->defaultItems(
                    fn($get) => QuestionType::tryFrom($get("type")) ===
                    QuestionType::Written
                        ? 1
                        : 2,
                )
                ->orderColumn("order")
                ->itemLabel(fn($state) => $state["name"] ?? "...")
                ->reorderableWithDragAndDrop()
                ->saveRelationshipsUsing(function (Question $record, $state) {})
                ->schema(Option::getForm()),
        ];
    }
}
