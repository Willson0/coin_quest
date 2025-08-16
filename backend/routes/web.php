<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TradeController;
use App\Http\Middleware\CheckAdminMiddleware;
use App\Http\Middleware\CheckTelegram;
use Illuminate\Support\Facades\Route;

Route::group(["prefix" => "api"], function () {
    Route::group(["prefix" => "auth", "middleware" => CheckTelegram::class], function () {
        Route::post("profile", [AuthController::class, "profile"]);
    });

    Route::group(["prefix" => "news", "middleware" => CheckTelegram::class], function () {
        Route::post("", [NewsController::class, "index"]);
    });

    Route::group(["prefix" => "lesson", "middleware" => CheckTelegram::class], function () {
        Route::post("/{lesson}", [LessonController::class, "show"]);
        Route::post("/{lesson}/check", [LessonController::class, "checkAnswers"]);
    });

    Route::group(["prefix" => "trade", "middleware" => CheckTelegram::class], function () {
        Route::post("", [TradeController::class, "trade"]);
        Route::post("send", [TradeController::class, "send"]);
    });

    Route::post("/admin/login", [AdminController::class, "login"]);
    Route::group(["prefix" => "admin", "middleware" => CheckAdminMiddleware::class], function () {
        Route::get("profile", [AdminController::class, "profile"]);
        Route::post("logout", [AdminController::class, "logout"]);
        Route::get("users", [AdminController::class, "users"]);
        Route::get("users/{user}", [AdminController::class, "showUser"]);
        Route::get('courses', [AdminController::class, "courses"]);
        Route::delete('courses/{course}', [AdminController::class, "deleteCourse"]);
        Route::post('courses/{course}', [AdminController::class, "updateCourse"]);
        Route::post('courses', [AdminController::class, "createCourse"]);
        Route::delete("lessons/{lesson}", [AdminController::class, "deleteLesson"]);
        Route::post("lessons/{lesson}", [AdminController::class, "updateLesson"]);
        Route::post("lessons", [AdminController::class, "createLesson"]);
        Route::get("news", [AdminController::class, "news"]);
        Route::post("news", [AdminController::class, "createNews"]);
        Route::post("news/{news}", [AdminController::class, "updateNews"]);
        Route::delete("news/{news}", [AdminController::class, "deleteNews"]);
        Route::post("news/category", [AdminController::class, "createNewsCategory"]);
        Route::delete("news/category/{category}", [AdminController::class, "deleteNewsCategory"]);
        Route::post("news/category/{category}", [AdminController::class, "updateNewsCategory"]);
        Route::get("tournaments", [AdminController::class, "tournaments"]);
        Route::post("tournaments", [AdminController::class, "createTournament"]);
        Route::post("tournaments/{tournament}", [AdminController::class, "updateTournament"]);
        Route::delete("tournaments/{tournament}", [AdminController::class, "deleteTournament"]);
        Route::get("currencies", [AdminController::class, "currencies"]);
        Route::post("currencies", [AdminController::class, "createCurrency"]);
        Route::post("currencies/{currency}", [AdminController::class, "updateCurrency"]);
        Route::delete("currencies/{currency}", [AdminController::class, "deleteCurrency"]);
        Route::get("achievements", [AdminController::class, "achievements"]);
        Route::post("achievements", [AdminController::class, "createAchievement"]);
        Route::post("achievements/{achievement}", [AdminController::class, "updateAchievement"]);
        Route::delete("achievements/{achievement}", [AdminController::class, "deleteAchievement"]);
    });

    Route::group(["prefix" => "stats", "middleware" => CheckAdminMiddleware::class], function () {
        Route::get("/", [StatsController::class, "index"]);
    });
});
