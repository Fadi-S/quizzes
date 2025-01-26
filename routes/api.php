<?php

use App\Http\Controllers\AllGamesController;
use App\Http\Controllers\CheckQuestionController;
use App\Http\Controllers\CurrentGameController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\User\SubmitQuizGuestController;
use App\Http\Middleware\EnsureApiKeyIsValid;
use Illuminate\Support\Facades\Route;

Route::prefix("v1")
    ->middleware(EnsureApiKeyIsValid::class)
    ->group(function () {
        Route::get("check", CurrentGameController::class);

        Route::get("all-games", AllGamesController::class);

        Route::resource("groups", GroupController::class);

        Route::resource("quizzes", QuizController::class)->except("show");
        Route::get("quizzes/{group}/{slug}", [QuizController::class, "show"]);

        Route::resource("entities", EntityController::class);

        Route::get("questions/{question}/check", [
            CheckQuestionController::class,
            "show",
        ]);

        Route::get("quizzes/{quiz}/check", [
            CheckQuestionController::class,
            "index",
        ]);
    });

Route::prefix("v1")->group(function () {
    Route::post(
        "quizzes/{group}/{slug}/submit",
        SubmitQuizGuestController::class,
    );
});
