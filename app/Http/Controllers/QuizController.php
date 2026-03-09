<?php

namespace App\Http\Controllers;

use App\Enums\QuestionType;
use App\Http\Resources\QuizResource;
use App\Models\EntityQuestion;
use App\Models\EntityQuiz;
use App\Models\Entity;
use App\Models\Group;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $quizzes = Quiz::query()
            ->orderBy("id", "desc")
            ->when(
                $request->has("published"),
                fn($query) => $query->published(),
            )
            ->withCount("questions");

        if ($request->has("entity")) {
            $entity = Entity::query()
                ->where("id", "=", $request->entity)
                ->first(["id", "group_id"]);

            if (!$entity) {
                return response()->json(["message" => "Entity not found"], 404);
            }

            $quizzes
                ->where("group_id", "=", $entity->group_id)
                ->leftJoin("entity_quizzes", function ($join) use ($entity) {
                    $join
                        ->on("entity_quizzes.quiz_id", "=", "quizzes.id")
                        ->where("entity_quizzes.entity_id", "=", $entity->id)
                        ->orWhereNull("entity_quizzes.entity_id");
                })
                ->selectRaw(
                    "quizzes.*, IFNULL(entity_quizzes.entity_id, 0) as is_solved, entity_quizzes.points as points_won",
                );
        }

        $quizzes = $quizzes->get();

        return response()->json([
            "quizzes" => QuizResource::collection($quizzes),
        ]);
    }

    private function saveTemporaryFile($path, $dir): ?string
    {
        if (!Storage::exists($path)) {
            return null;
        }

        $filename = basename($path);

        $fileContent = Storage::get($path);

        $tempFilePath = tempnam(sys_get_temp_dir(), "s3file");
        file_put_contents($tempFilePath, $fileContent);

        $file = new File($tempFilePath);

        $newPath = Storage::disk(
            config("filament.default_filesystem_disk"),
        )->putFileAs($dir, $file, $filename);

        unlink($tempFilePath);

        Storage::delete($path);

        return $newPath;
    }

    private function validateAnsweredQuestionEdits(Quiz $quiz, array $questions): void
    {
        $quiz->loadMissing("questions.options", "responses.answers");

        $answeredQuestionIds = $quiz->responses
            ->flatMap(fn($response) => $response->answers->pluck("question_id"))
            ->unique()
            ->values();

        if ($answeredQuestionIds->isEmpty()) {
            return;
        }

        $currentQuestions = $quiz->questions->keyBy("id");
        $incomingQuestions = collect($questions)
            ->filter(fn($question) => isset($question["id"]))
            ->keyBy("id");

        $deletedAnsweredQuestionIds = $answeredQuestionIds->diff(
            $incomingQuestions->keys(),
        );

        if ($deletedAnsweredQuestionIds->isNotEmpty()) {
            abort(
                422,
                "Answered questions cannot be deleted after users have responded.",
            );
        }

        foreach ($answeredQuestionIds as $questionId) {
            $currentQuestion = $currentQuestions->get($questionId);
            $incomingQuestion = $incomingQuestions->get($questionId);

            if (!$currentQuestion || !$incomingQuestion) {
                continue;
            }

            if ((int) $currentQuestion->type->value !== (int) $incomingQuestion["type"]) {
                abort(
                    422,
                    "Question type cannot be changed after users have responded.",
                );
            }

            if (
                in_array(
                    $currentQuestion->type,
                    [
                        QuestionType::Choose,
                        QuestionType::MultipleChoose,
                        QuestionType::Order,
                    ],
                    true,
                )
            ) {
                $currentOptionIds = $currentQuestion->options
                    ->sortBy("order")
                    ->pluck("id")
                    ->values();
                $incomingOptionIds = collect($incomingQuestion["options"] ?? [])
                    ->map(fn($option) => $option["id"] ?? null)
                    ->values();

                if (
                    $incomingOptionIds->contains(null) ||
                    $currentOptionIds->count() !== $incomingOptionIds->count() ||
                    $currentOptionIds->values()->all() !== $incomingOptionIds->values()->all()
                ) {
                    abort(
                        422,
                        "Answered choice and reorder questions cannot have options added, removed, or reordered.",
                    );
                }
            }
        }
    }

    private function getChangedExistingQuestionIds(
        Quiz $quiz,
        array $questions,
    ): Collection {
        $quiz->loadMissing("questions.options");

        $currentQuestions = $quiz->questions->keyBy("id");

        return collect($questions)
            ->filter(fn($question) => isset($question["id"]))
            ->filter(function ($question) use ($currentQuestions) {
                $currentQuestion = $currentQuestions->get($question["id"]);

                if (!$currentQuestion) {
                    return false;
                }

                $currentSnapshot = [
                    "title" => $currentQuestion->title,
                    "type" => $currentQuestion->type->value,
                    "points" => $currentQuestion->points,
                    "picture" => $currentQuestion->picture,
                    "correct_answers" => collect($currentQuestion->correct_answers)
                        ->map(fn($answer) => (int) $answer)
                        ->values()
                        ->all(),
                    "options" => $currentQuestion->options
                        ->sortBy("order")
                        ->values()
                        ->map(fn($option) => [
                            "id" => $option->id,
                            "name" => $option->name,
                            "order" => $option->order,
                            "picture" => $option->picture,
                        ])
                        ->all(),
                ];

                $incomingSnapshot = [
                    "title" => $question["title"],
                    "type" => (int) $question["type"],
                    "points" => isset($question["points"])
                        ? (int) $question["points"]
                        : null,
                    "picture" => ($question["picture"] ?? null) === "removed"
                        ? null
                        : ($question["picture"] ?? null),
                    "correct_answers" => collect($question["correct_answers"] ?? [])
                        ->map(fn($answer) => (int) $answer)
                        ->values()
                        ->all(),
                    "options" => collect($question["options"] ?? [])
                        ->values()
                        ->map(fn($option) => [
                            "id" => $option["id"] ?? null,
                            "name" => $option["name"],
                            "order" => (int) $option["order"],
                            "picture" => ($option["picture"] ?? null) === "removed"
                                ? null
                                : ($option["picture"] ?? null),
                        ])
                        ->all(),
                ];

                return $currentSnapshot !== $incomingSnapshot;
            })
            ->pluck("id")
            ->values();
    }

    private function rescoreChangedQuestions(Quiz $quiz, Collection $questionIds): array
    {
        if ($questionIds->isEmpty()) {
            return [
                "questions_count" => 0,
                "responses_count" => 0,
                "users_count" => 0,
                "total_delta_points" => 0,
                "items" => [],
            ];
        }

        $questions = Question::query()
            ->with("options")
            ->whereIn("id", $questionIds)
            ->get()
            ->keyBy("id");

        $responses = EntityQuestion::query()
            ->with(["question.options", "entityQuiz.entity"])
            ->whereIn("question_id", $questionIds)
            ->get();

        $items = [];
        $totalDeltaPoints = 0;

        DB::transaction(function () use (
            $responses,
            $questions,
            &$items,
            &$totalDeltaPoints,
        ) {
            foreach ($responses as $response) {
                $question = $questions->get($response->question_id);
                $entityQuiz = $response->entityQuiz;

                if (!$question || !$entityQuiz) {
                    continue;
                }

                $storedAnswer = $response->answer;
                $check = $question->check($storedAnswer);
                $oldPoints = (int) $response->points;
                $newPoints = (int) $check->points;
                $deltaPoints = $newPoints - $oldPoints;
                $oldCorrect = (bool) $response->is_correct;
                $newCorrect = (bool) $check->isCorrect;

                if (
                    $deltaPoints === 0 &&
                    $oldCorrect === $newCorrect &&
                    $response->answer == $check->response
                ) {
                    continue;
                }

                $response->answer = $check->response;
                $response->is_correct = $newCorrect;
                $response->points = $newPoints;
                $response->save();

                $entityQuiz->points += $deltaPoints;
                $entityQuiz->save();

                $totalDeltaPoints += $deltaPoints;

                $items[] = [
                    "response_id" => $response->id,
                    "entity_quiz_id" => $entityQuiz->id,
                    "entity_id" => $entityQuiz->entity_id,
                    "entity_name" => $entityQuiz->entity?->name,
                    "quiz_id" => $entityQuiz->quiz_id,
                    "question_id" => $question->id,
                    "old_points" => $oldPoints,
                    "new_points" => $newPoints,
                    "delta_points" => $deltaPoints,
                    "old_is_correct" => $oldCorrect,
                    "new_is_correct" => $newCorrect,
                ];
            }
        });

        return [
            "questions_count" => $questionIds->count(),
            "responses_count" => count($items),
            "users_count" => collect($items)->pluck("entity_id")->unique()->count(),
            "total_delta_points" => $totalDeltaPoints,
            "items" => array_values($items),
        ];
    }

    private function saveQuestions(Quiz $quiz, array $questions)
    {
        $optionsToBeSaved = [];

        $questionsKept = [];

        $quiz->load("questions.options");

        $currentQuestions = $quiz->questions->keyBy("id");

        foreach ($questions as $data) {
            $removePicture = ($data["picture"] ?? null) === "removed";
            if ($removePicture) {
                $data["picture"] = null;
            }

            if (isset($data["picture"]) && $data["picture"]) {
                $data["picture"] = $this->saveTemporaryFile(
                    $data["picture"],
                    "questions",
                );
            }

            $dataSaved = [
                "title" => $data["title"],
                "type" => $data["type"],
                "picture" => $data["picture"] ?? null,
                "points" => $data["points"] ?? null,
                "correct_answers" => $data["correct_answers"] ?? [],
            ];

            $question = $currentQuestions->get($data["id"] ?? null);
            if ($question) {
                if (!$removePicture && !$dataSaved["picture"]) {
                    $dataSaved["picture"] = $question->picture;
                } elseif (
                    $question->picture &&
                    ($removePicture || $dataSaved["picture"])
                ) {
                    Storage::delete($question->picture);
                }

                $question->update($dataSaved);
            } else {
                $question = $quiz->questions()->create($dataSaved);
            }

            $questionsKept[] = $question->id;

            $optionsByName = $question->options->keyBy("id");

            foreach ($data["options"] as $option) {
                $removePicture = ($option["picture"] ?? null) === "removed";
                if ($removePicture) {
                    $option["picture"] = null;
                }

                if (isset($option["picture"]) && $option["picture"]) {
                    $option["picture"] = $this->saveTemporaryFile(
                        $option["picture"],
                        "options",
                    );
                }

                $currentOption = $optionsByName->get($option["id"] ?? null);

                if ($currentOption) {
                    if (!$removePicture && !$option["picture"]) {
                        $option["picture"] = $currentOption->picture;
                    } elseif (
                        $currentOption->picture &&
                        ($removePicture || $option["picture"])
                    ) {
                        Storage::delete($currentOption->picture);
                    }
                }

                $optionsToBeSaved[] = [
                    "question_id" => $question->id,
                    "name" => $option["name"],
                    "order" => $option["order"],
                    "picture" => $option["picture"] ?? null,
                ];

                $question
                    ->options()
                    ->whereNotIn(
                        "name",
                        collect($data["options"])->pluck("name"),
                    )
                    ->delete();
            }
        }

        $quiz->questions()->whereNotIn("id", $questionsKept)->delete();

        Option::upsert(
            $optionsToBeSaved,
            ["name", "question_id"],
            ["name", "picture", "order"],
        );
    }

    private function rules($groupId, $ignore = null): array
    {
        return [
            "name" => [
                "required",
                "string",
                "max:255",
                Rule::unique("quizzes", "name")
                    ->where("group_id", $groupId)
                    ->ignore($ignore),
            ],
            "data" => ["nullable", "array"],
            "published_at" => ["required", "date"],
            "available_until" => ["nullable", "date", "after:published_at"],
            "questions" => ["nullable", "array"],
            "questions.*.title" => ["required", "string", "max:500"],
            "questions.*.picture" => ["nullable"],
            "questions.*.points" => [
                "nullable",
                "numeric",
                "min:0",
                "max:65535",
            ],
            "questions.*.type" =>
                "required|numeric|in:" .
                implode(
                    ",",
                    array_map(fn($case) => $case->value, QuestionType::cases()),
                ),
            "questions.*.correct_answers" => ["required", "array"],
            "questions.*.options" => ["nullable", "array"],
            "questions.*.options.*.name" => ["required", "string", "max:255"],
            "questions.*.options.*.order" => [
                "required",
                "numeric",
                "min:1",
                "max:255",
            ],
            "questions.*.options.*.picture" => ["nullable"],
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $group = Group::query()
            ->where("slug", "=", $request->group)
            ->firstOrFail();

        $request->validate(
            array_merge($this->rules($group->id), [
                "group" => "required|string|exists:groups,slug",
            ]),
        );

        $quiz = Quiz::create([
            "name" => $request->name,
            "group_id" => $group->id,
            "data" => $request->data,
            "published_at" => $request->published_at,
        ]);

        if ($request->has("questions")) {
            $this->saveQuestions($quiz, $request->questions);
        }

        return response()->json([
            "message" => "Quiz created successfully",
            "quiz" => QuizResource::make($quiz),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($group, $slug, Request $request)
    {
        $quiz = Quiz::query()
            ->whereRelation("group", "slug", "=", $group)
            ->where("slug", $slug)
            ->with("questions.options")
            ->when(
                $request->has("withResponses"),
                fn($query) => $query->with([
                    "responses" => fn($q) => $q->with(["entity", "answers"]),
                ]),
            )
            ->firstOrFail();

        return response()->json([
            "quiz" => QuizResource::make($quiz),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Quiz $quiz)
    {
        $request->validate($this->rules($quiz->group_id, $quiz->id));

        $questions = $request->questions ?? [];
        $this->validateAnsweredQuestionEdits($quiz, $questions);
        $changedExistingQuestionIds = $this->getChangedExistingQuestionIds(
            $quiz,
            $questions,
        );

        $quiz->update([
            "name" => $request->name,
            "data" => $request->data,
            "published_at" => $request->published_at,
        ]);

        if ($request->has("questions")) {
            $this->saveQuestions($quiz, $questions);
        }

        $rescoreSummary = $this->rescoreChangedQuestions(
            $quiz,
            $changedExistingQuestionIds,
        );

        $quiz->load("questions.options");

        return response()->json([
            "message" => "Quiz updated successfully",
            "quiz" => QuizResource::make($quiz),
            "rescore_summary" => $rescoreSummary,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Quiz $quiz)
    {
        $quiz->delete();

        return response()->json([
            "message" => "Quiz deleted successfully",
            "quiz" => QuizResource::make($quiz),
        ]);
    }
}
