<?php

namespace App\Http\Controllers;

use App\Enums\QuestionType;
use App\Http\Resources\QuizResource;
use App\Models\Entity;
use App\Models\Group;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $quizzes = Quiz::query()
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

    private function saveQuestions(Quiz $quiz, array $questions)
    {
        $optionsToBeSaved = [];

        $questionsKept = [];
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

            if (isset($data["id"])) {
                $question = $quiz
                    ->questions()
                    ->where("id", "=", $data["id"])
                    ->first();

                if (!$removePicture && !$dataSaved["picture"]) {
                    $dataSaved["picture"] = $question->picture;
                }

                if (
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
            "questions" => ["nullable", "array"],
            "questions.*.title" => ["required", "string", "max:255"],
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

        $quiz->update([
            "name" => $request->name,
            "data" => $request->data,
            "published_at" => $request->published_at,
        ]);

        if ($request->has("questions")) {
            $this->saveQuestions($quiz, $request->questions);
        }

        return response()->json([
            "message" => "Quiz updated successfully",
            "quiz" => QuizResource::make($quiz),
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
