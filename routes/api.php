<?php

use App\Http\Controllers\AllGamesController;
use App\Http\Controllers\CheckQuestionController;
use App\Http\Controllers\CurrentGameController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\Admin\QuizStatsController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizResponseController;
use App\Http\Controllers\RecentEntityQuizzesController;
use App\Http\Controllers\ResponsesController;
use App\Http\Controllers\SaveFileTemporarilyController;
use App\Http\Controllers\SubmitQuizController;
use App\Http\Controllers\User\SubmitQuizGuestController;
use App\Http\Middleware\EnsureApiKeyIsValid;
use App\Http\Middleware\EnsureApiKeyIsValidForAdmin;
use Illuminate\Support\Facades\Route;

Route::prefix("v1")
    ->middleware(EnsureApiKeyIsValidForAdmin::class)
    ->group(function () {
        Route::get("check", CurrentGameController::class);

        Route::get("all-games", AllGamesController::class);

        Route::resource("groups", GroupController::class);

        Route::get("/upload/key", [
            SaveFileTemporarilyController::class,
            "url",
        ]);

        Route::patch("/responses/{response}", [
            ResponsesController::class,
            "update",
        ]);

        Route::patch("/responses/{response}/correct", [
            ResponsesController::class,
            "markAsCorrect",
        ])->name("responses.mark-as-correct");

        Route::delete("/responses/{response}", [
            ResponsesController::class,
            "delete",
        ]);

        Route::resource("quizzes", QuizController::class)->except("show");

        Route::get("quizzes/stats/summary", [
            QuizStatsController::class,
            "summary",
        ]);

        Route::get("quizzes/stats/difficulty", [
            QuizStatsController::class,
            "difficulty",
        ]);

        Route::get("quizzes/stats/questions/hardest", [
            QuizStatsController::class,
            "hardestQuestions",
        ]);

        Route::get("quizzes/stats/questions/by-quiz", [
            QuizStatsController::class,
            "hardestQuestionsByQuiz",
        ]);

        Route::get("quizzes/stats/quizzes/{slug}/questions/hardest", [
            QuizStatsController::class,
            "hardestQuestionsForQuiz",
        ]);

        Route::get("quizzes/stats/questions/{question}/distribution", [
            QuizStatsController::class,
            "questionDistribution",
        ]);

        Route::get("quizzes/stats/attempts", [
            QuizStatsController::class,
            "attemptCounts",
        ]);

        Route::get("quizzes/stats/published-count", [
            QuizStatsController::class,
            "publishedCount",
        ]);

        Route::get(
            "quizzes/{group}/{slug}/responses",
            QuizResponseController::class,
        );

        Route::get("questions/{question}/check", [
            CheckQuestionController::class,
            "show",
        ]);

        Route::get("quizzes/{quiz}/check", [
            CheckQuestionController::class,
            "index",
        ]);

        Route::post(
            "quizzes/{group}/{slug}/{entity}/submit",
            SubmitQuizController::class,
        );

        Route::post("entities/bulk", [EntityController::class, "storeBulk"]);

        Route::apiResource("entities", EntityController::class);

        Route::get("entity-quizzes/recent", RecentEntityQuizzesController::class);
    });

Route::prefix("v1")
    ->middleware(EnsureApiKeyIsValid::class)
    ->group(function () {
        Route::get("quizzes/{group}/{slug}", [QuizController::class, "show"]);

        Route::post(
            "quizzes/{group}/{slug}/submit",
            SubmitQuizGuestController::class,
        );
    });

Route::prefix("v1")
    ->middleware("signed")
    ->group(function () {
        Route::post("upload", [
            SaveFileTemporarilyController::class,
            "upload",
        ])->name("upload");

        Route::get("/proxy", [
            SaveFileTemporarilyController::class,
            "proxy",
        ])->name("proxy");
    });
