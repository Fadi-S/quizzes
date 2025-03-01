<?php

namespace App\Models;

use App\Enums\QuestionType;
use App\Questions\QuestionResponse;
use App\Questions\QuizResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Filament\Forms;

class Quiz extends Model
{
    use HasRelationships;

    protected $casts = [
        "data" => "json",
        "published_at" => "datetime",
    ];

    protected static function booting()
    {
        static::addGlobalScope("game", function ($model) {
            $model->whereRelation(
                "group",
                "game_id",
                "=",
                Game::current()?->id,
            );
        });

        static::addGlobalScope("addPoints", function ($model) {
            $model->addPoints();
        });

        parent::booting();
    }

    protected static function boot()
    {
        static::creating(function ($model) {
            $model->slug = self::getSlug($model->name);
        });

        parent::boot();
    }

    private static function getSlug($name): string
    {
        return str($name)->slug(language: "en");
    }

    public static function fromGroupAndSlug($group, $quizSlug): ?self
    {
        return Quiz::query()
            ->whereRelation("group", "slug", "=", $group)
            ->where("slug", $quizSlug)
            ->with("questions.options")
            ->first();
    }

    public function correct($questions): QuizResponse
    {
        $this->load("questions.options");

        $points = 0;
        $answers = [];
        $responses = [];

        foreach ($this->questions as $question) {
            $answers[$question->id] = $question->getAnswers();

            if (!isset($questions[$question->id])) {
                $responses[$question->id] = QuestionResponse::noResponse();
                continue;
            }

            $answer = $questions[$question->id];
            $responses[$question->id] = $question->check($answer);

            $points += $responses[$question->id]->points;
        }

        return new QuizResponse($points, $responses, $answers);
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

    public function responses(): HasMany
    {
        return $this->hasMany(EntityQuiz::class);
    }

    public function isPublished(): bool
    {
        return $this->published_at?->isPast();
    }

    public function scopeAddPoints($query): void
    {
        $query->addSelect([
            "points" => Question::query()
                ->selectRaw("SUM(points)")
                ->whereColumn("questions.quiz_id", "quizzes.id"),
        ]);
    }

    public function scopePublished($query): void
    {
        $query->where("published_at", "<=", now()->format("Y-m-d H:i:s"));
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
                        $set("slug", self::getSlug($state));
                    }
                })
                ->unique(
                    ignorable: fn($record) => $record ?? null,
                    ignoreRecord: true,
                    modifyRuleUsing: function ($rule, $get) {
                        return $rule->where("group_id", $get("group_id"));
                    },
                )
                ->required(),

            Forms\Components\TextInput::make("slug")->disabled(),

            Forms\Components\Select::make("group_id")
                ->label("Group")
                ->options(fn() => Group::pluck("name", "id"))
                ->required()
                ->searchable(),

            Forms\Components\DateTimePicker::make("published_at")
                ->label("Published At")
                ->required(),

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

                    $questionIdsInState = collect();
                    foreach ($state as $question) {
                        $options = $question["options"];
                        $question[
                            "correct_answers"
                        ] = Question::calculateCorrectAnswers(
                            QuestionType::tryFrom($question["type"]),
                            $options,
                        );

                        unset($question["options"]);
                        $order = 1;
                        foreach ($options as $key => $option) {
                            unset($option["is_correct"]);
                            unset($option["id"]);

                            $option["order"] = $order++;
                            $option["picture"] = collect(
                                $option["picture"],
                            )->first();

                            $options[$key] = $option;
                        }

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

                        $questionIdsInState->push($question->id);

                        $options = collect($options)->map(
                            fn($option) => [
                                ...$option,
                                "question_id" => $question->id,
                            ],
                        );

                        $allOptions->push(...$options);
                    }

                    Option::upsert(
                        $allOptions->all(),
                        ["name", "question_id"],
                        ["name", "picture", "order"],
                    );

                    $record
                        ->questions()
                        ->whereNotIn("id", $questionIdsInState)
                        ->delete();
                })
                ->schema(Question::getForm()),
        ];
    }
}
