<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Filament\Forms;

class Quiz extends Model
{
    use HasRelationships;

    protected static function boot()
    {
        static::creating(function ($model) {
            $model->slug = str($model->name)->slug(language: null);
        });

        parent::boot();
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function game(): HasOneThrough
    {
        return $this->hasOneThrough(
            Game::class,
            Group::class,
            "id",
            "id",
            "group_id",
            "game_id",
        );
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->with("options");
    }

    public static function getForm(): array
    {
        return [
            Forms\Components\TextInput::make("name")
                ->live(onBlur: true)
                ->afterStateUpdated(function (
                    string $operation,
                    $state,
                    Forms\Set $set,
                ) {
                    if ($operation === "create") {
                        $set("slug", str($state)->slug(language: null));
                    }
                })
                ->required(),

            Forms\Components\TextInput::make("slug")->disabled(),

            Forms\Components\Select::make("group_id")
                ->label("Group")
                ->options(fn() => Group::pluck("name", "id"))
                ->required()
                ->searchable(),
            Forms\Components\Repeater::make("questions")
                ->columnSpan("full")
                ->relationship()
                ->collapsible()
                ->collapsed(fn($record) => $record?->exists)
                ->cloneable()
                ->itemLabel(fn($state) => $state["title"] ?? "New Question *")
                ->defaultItems(0)
                ->saveRelationshipsUsing(function (Quiz $record, $state) {
                    $allOptions = collect();
                    foreach ($state as $question) {
                        $i = 1;
                        $correctAnswers = collect();
                        $options = $question["options"];
                        unset($question["options"]);
                        foreach ($options as $key => $option) {
                            $order = $i++;
                            if ($option["is_correct"]) {
                                $correctAnswers->push($order);
                            }
                            unset($option["is_correct"]);
                            unset($option["id"]);

                            $option["order"] = $order;
                            $option["picture"] = collect(
                                $option["picture"],
                            )->first();

                            $options[$key] = $option;
                        }

                        $question["correct_answers"] = $correctAnswers->all();
                        $question["picture"] = collect(
                            $question["picture"],
                        )->first();

                        if (isset($question["id"])) {
                            $data = $question;
                            $question = $record
                                ->questions()
                                ->where("id", "=", $data["id"])
                                ->first();

                            $question->update($data);
                        } else {
                            $question = $record->questions()->create($question);
                        }

                        $options = collect($options)->map(function (
                            $option,
                        ) use ($question) {
                            $option["question_id"] = $question->id;
                            return $option;
                        });

                        $allOptions->push(...$options);
                    }

                    Option::upsert(
                        $allOptions->all(),
                        ["name", "question_id"],
                        ["name", "picture", "order"],
                    );
                })
                ->schema(Question::getForm()),
        ];
    }
}
