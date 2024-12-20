<?php

use App\Http\Controllers\AllGamesController;
use App\Http\Controllers\CurrentGameController;
use Illuminate\Support\Facades\Route;

Route::prefix("v1")->group(function () {
    Route::get("check", CurrentGameController::class);
    Route::get("all-games", AllGamesController::class);
});
