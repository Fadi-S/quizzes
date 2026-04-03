<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Models\EntityQuestion;
use App\Models\Group;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizStatsController extends Controller
{
    private const MIN_SUBMISSIONS = 3;
    private const MIN_ATTEMPTS = 3;

    public function summary(Request $request)
    {
        $group = $this->resolveGroup($request);
        $quizzes = $this->publishedQuizzesQuery($group)
            ->withCount("questions")
            ->get(["id"]);

        return response()->json([
            "summary" => [
                "quizzesCount" => $quizzes->count(),
                "questionsCount" => $quizzes->sum("questions_count"),
            ],
        ]);
    }

    public function difficulty(Request $request)
    {
        $group = $this->resolveGroup($request);

        $quizzes = $this->quizDifficultyQuery($group)->get()
            ->map(fn($quiz) => [
                "quizId" => $quiz->quiz_id,
                "quizSlug" => $quiz->quiz_slug,
                "quizName" => $quiz->quiz_name,
                "submissionsCount" => (int) $quiz->submissions_count,
                "totalAnswers" => (int) $quiz->total_answers,
                "correctAnswers" => (int) $quiz->correct_answers,
                "accuracy" => (float) $quiz->accuracy,
            ])
            ->values();

        return response()->json([
            "quizzes" => $quizzes,
        ]);
    }

    public function hardestQuestions(Request $request)
    {
        $group = $this->resolveGroup($request);

        $questions = $this->hardestQuestionsBaseQuery($group)
            ->limit($this->normalizeLimit($request->integer("limit", 10)))
            ->get()
            ->map(fn($question) => [
                "quizId" => $question->quiz_id,
                "quizSlug" => $question->quiz_slug,
                "quizName" => $question->quiz_name,
                "questionId" => $question->question_id,
                "questionTitle" => $question->question_title,
                "questionType" => $question->question_type,
                "attempts" => (int) $question->attempts,
                "correctAnswers" => (int) $question->correct_answers,
                "accuracy" => (float) $question->accuracy,
            ])
            ->values();

        return response()->json([
            "questions" => $questions,
        ]);
    }

    public function hardestQuestionsByQuiz(Request $request)
    {
        $group = $this->resolveGroup($request);
        $limit = $this->normalizePerQuizLimit($request->integer("limit", 3));

        $quizzes = $this->quizDifficultyQuery($group)
            ->select([
                "quiz_id",
                "quiz_slug",
                "quiz_name",
            ])
            ->get();

        $questionsByQuiz = $this->hardestQuestionsBaseQuery($group)
            ->get()
            ->groupBy("quiz_id");

        $items = $quizzes
            ->map(function ($quiz) use ($questionsByQuiz, $limit) {
                $questions = $questionsByQuiz
                    ->get($quiz->quiz_id, collect())
                    ->take($limit)
                    ->values();

                if ($questions->isEmpty()) {
                    return null;
                }

                return [
                    "quizId" => $quiz->quiz_id,
                    "quizSlug" => $quiz->quiz_slug,
                    "quizName" => $quiz->quiz_name,
                    "questions" => $questions->map(fn($question) => [
                        "quizId" => $question->quiz_id,
                        "quizSlug" => $question->quiz_slug,
                        "quizName" => $question->quiz_name,
                        "questionId" => $question->question_id,
                        "questionTitle" => $question->question_title,
                        "questionType" => $question->question_type,
                        "attempts" => (int) $question->attempts,
                        "correctAnswers" => (int) $question->correct_answers,
                        "accuracy" => (float) $question->accuracy,
                    ])->all(),
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            "quizzes" => $items,
        ]);
    }

    public function hardestQuestionsForQuiz(Request $request, string $slug)
    {
        $group = $this->resolveGroup($request);

        $questions = $this->hardestQuestionsBaseQuery($group)
            ->where("quizzes.slug", $slug)
            ->limit($this->normalizeLimit($request->integer("limit", 10)))
            ->get()
            ->map(fn($question) => [
                "quizId" => $question->quiz_id,
                "quizSlug" => $question->quiz_slug,
                "quizName" => $question->quiz_name,
                "questionId" => $question->question_id,
                "questionTitle" => $question->question_title,
                "questionType" => $question->question_type,
                "attempts" => (int) $question->attempts,
                "correctAnswers" => (int) $question->correct_answers,
                "accuracy" => (float) $question->accuracy,
            ])
            ->values();

        return response()->json([
            "questions" => $questions,
        ]);
    }

    public function questionDistribution(Request $request, Question $question)
    {
        $group = $this->resolveGroup($request);
        $question->loadMissing("quiz.group", "options");

        abort_unless(
            $question->quiz
            && $question->quiz->group
            && $question->quiz->group->id === $group->id
            && $question->quiz->isPublished()
            && in_array($question->type, [QuestionType::Choose, QuestionType::MultipleChoose], true),
            404,
        );

        $responses = EntityQuestion::query()
            ->where("question_id", $question->id)
            ->get(["answer"]);

        $counts = $question->options
            ->mapWithKeys(fn($option) => [(int) $option->order => 0]);

        $totalResponses = 0;
        foreach ($responses as $response) {
            $selections = collect(is_array($response->answer) ? $response->answer : [$response->answer])
                ->filter(fn($value) => is_numeric($value))
                ->map(fn($value) => (int) $value)
                ->values();

            if ($selections->isEmpty()) {
                continue;
            }

            $totalResponses++;
            foreach ($selections as $selection) {
                if ($counts->has($selection)) {
                    $counts[$selection] = $counts[$selection] + 1;
                }
            }
        }

        $correctAnswers = collect($question->correct_answers)
            ->filter(fn($value) => is_numeric($value))
            ->map(fn($value) => (int) $value)
            ->values();

        return response()->json([
            "distribution" => [
                "quizId" => $question->quiz->id,
                "quizSlug" => $question->quiz->slug,
                "quizName" => $question->quiz->name,
                "questionId" => $question->id,
                "questionTitle" => $question->title,
                "totalResponses" => $totalResponses,
                "options" => $question->options
                    ->sortBy("order")
                    ->values()
                    ->map(fn($option) => [
                        "optionId" => $option->id,
                        "optionName" => $option->name,
                        "optionOrder" => (int) $option->order,
                        "picksCount" => (int) $counts[(int) $option->order],
                        "picksPercentage" => $totalResponses === 0
                            ? 0
                            : $counts[(int) $option->order] / $totalResponses,
                        "correct" => $correctAnswers->contains((int) $option->order),
                    ])
                    ->all(),
            ],
        ]);
    }

    public function attemptCounts(Request $request)
    {
        $group = $this->resolveGroup($request);

        $attempts = DB::table("entity_quizzes")
            ->join("quizzes", "quizzes.id", "=", "entity_quizzes.quiz_id")
            ->join("groups", "groups.id", "=", "quizzes.group_id")
            ->where("groups.id", $group->id)
            ->whereNotNull("quizzes.published_at")
            ->where("quizzes.published_at", "<=", now())
            ->groupBy("entity_quizzes.entity_id")
            ->orderByRaw("COUNT(DISTINCT entity_quizzes.quiz_id) DESC")
            ->orderBy("entity_quizzes.entity_id")
            ->get([
                "entity_quizzes.entity_id as entityId",
                DB::raw("COUNT(DISTINCT entity_quizzes.quiz_id) as attemptedQuizzesCount"),
            ]);

        return response()->json([
            "attempts" => $attempts,
        ]);
    }

    public function publishedCount(Request $request)
    {
        $group = $this->resolveGroup($request);

        return response()->json([
            "count" => $this->publishedQuizzesQuery($group)->count(),
        ]);
    }

    private function resolveGroup(Request $request): Group
    {
        $slug = $request->string("group")->toString();

        abort_if($slug === "", 422, "Missing group");

        return Group::query()
            ->where("slug", $slug)
            ->firstOrFail();
    }

    private function publishedQuizzesQuery(Group $group): Builder
    {
        return Quiz::query()
            ->where("group_id", $group->id)
            ->published();
    }

    private function quizDifficultyQuery(Group $group)
    {
        return Quiz::query()
            ->where("quizzes.group_id", $group->id)
            ->published()
            ->leftJoin("entity_quizzes", "entity_quizzes.quiz_id", "=", "quizzes.id")
            ->leftJoin("entity_question", "entity_question.entity_quiz_id", "=", "entity_quizzes.id")
            ->groupBy("quizzes.id", "quizzes.slug", "quizzes.name")
            ->havingRaw("COUNT(DISTINCT entity_quizzes.id) >= ?", [self::MIN_SUBMISSIONS])
            ->havingRaw("COUNT(entity_question.id) > 0")
            ->orderByRaw("COALESCE(SUM(CASE WHEN entity_question.is_correct = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(entity_question.id), 0), 0) ASC")
            ->orderByRaw("COUNT(DISTINCT entity_quizzes.id) DESC")
            ->orderBy("quizzes.id")
            ->selectRaw("
                quizzes.id as quiz_id,
                quizzes.slug as quiz_slug,
                quizzes.name as quiz_name,
                COUNT(DISTINCT entity_quizzes.id) as submissions_count,
                COUNT(entity_question.id) as total_answers,
                COALESCE(SUM(CASE WHEN entity_question.is_correct = 1 THEN 1 ELSE 0 END), 0) as correct_answers,
                COALESCE(SUM(CASE WHEN entity_question.is_correct = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(entity_question.id), 0), 0) as accuracy
            ");
    }

    private function hardestQuestionsBaseQuery(Group $group)
    {
        return DB::table("entity_question")
            ->join("questions", "questions.id", "=", "entity_question.question_id")
            ->join("quizzes", "quizzes.id", "=", "questions.quiz_id")
            ->join("groups", "groups.id", "=", "quizzes.group_id")
            ->where("groups.id", $group->id)
            ->whereNotNull("quizzes.published_at")
            ->where("quizzes.published_at", "<=", now())
            ->groupBy(
                "quizzes.id",
                "quizzes.slug",
                "quizzes.name",
                "questions.id",
                "questions.title",
                "questions.type"
            )
            ->havingRaw("COUNT(entity_question.id) >= ?", [self::MIN_ATTEMPTS])
            ->orderByRaw("COALESCE(SUM(CASE WHEN entity_question.is_correct = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(entity_question.id), 0), 0) ASC")
            ->orderByRaw("COUNT(entity_question.id) DESC")
            ->orderBy("questions.id")
            ->selectRaw("
                quizzes.id as quiz_id,
                quizzes.slug as quiz_slug,
                quizzes.name as quiz_name,
                questions.id as question_id,
                questions.title as question_title,
                CASE questions.type
                    WHEN 1 THEN 'Choice'
                    WHEN 2 THEN 'Written'
                    WHEN 3 THEN 'Reorder'
                    WHEN 6 THEN 'MultipleCorrectChoices'
                    ELSE 'Unknown'
                END as question_type,
                COUNT(entity_question.id) as attempts,
                COALESCE(SUM(CASE WHEN entity_question.is_correct = 1 THEN 1 ELSE 0 END), 0) as correct_answers,
                COALESCE(SUM(CASE WHEN entity_question.is_correct = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(entity_question.id), 0), 0) as accuracy
            ");
    }

    private function normalizeLimit(int $limit): int
    {
        return $limit <= 10 ? 10 : 20;
    }

    private function normalizePerQuizLimit(int $limit): int
    {
        return $limit <= 0 ? 3 : min($limit, 10);
    }
}
