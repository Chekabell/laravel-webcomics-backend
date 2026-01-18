<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ComicController;
use App\Http\Controllers\API\ChapterController;
use App\Http\Controllers\API\RateController;
use App\Http\Controllers\API\CommentController;

// Публичные маршруты
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Аутентификация опциональна
Route::middleware('optional.auth')->group(function () {
    Route::get('/comics', [ComicController::class, 'index']);
    Route::get('/comics/popular', [ComicController::class, 'popular']);
    Route::get('/comics/newest', [ComicController::class, 'newest']);
    Route::get('/comics/featured', [ComicController::class, 'featured']);
    Route::get('/comics/{comic}', [ComicController::class, 'show']);
    Route::get('/comics/{comic}/chapters', [ChapterController::class, 'index']);
    Route::get('/comics/{comic}/comments', [CommentController::class, 'index']);
    Route::get('/comics/{comic}/chapters/{chapter}', [ChapterController::class, 'show']);
});

// Аутентифицированные пользователи
Route::middleware('auth:sanctum')->group(function () {
    // Аутентификация
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Профиль пользователя
    Route::put('/password', [UserController::class, 'updatePassword']);
    Route::post('/avatar', [UserController::class, 'uploadAvatar']);
    Route::delete('/avatar', [UserController::class, 'deleteAvatar']);

    // Рейтинги
    Route::post('/comics/{comic}/rate', [RateController::class, 'store']);
    Route::put('/comics/{comic}/rates/{rate}', [RateController::class, 'update']);
    Route::delete('/comics/{comic}/rates/{rate}', [RateController::class, 'destroy']);

    // Комментарии
    Route::post('/comics/{comic}/comments', [CommentController::class, 'store']);
    Route::put('/comics/{comic}/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comics/{comic}/comments/{comment}', [CommentController::class, 'destroy']);

    // Писатели
    Route::middleware('writer')->group(function () {
        // Создание комиксов
        Route::post('/comics', [ComicController::class, 'store']);
        Route::put('/comics/{comic}', [ComicController::class, 'update']);
        Route::delete('/comics/{comic}', [ComicController::class, 'destroy']);

        // Добавление глав
        Route::post('/comics/{comic}/chapters', [ChapterController::class, 'store']);
        Route::put('/comics/{comic}/chapters/{chapter}', [ChapterController::class, 'update']);
        Route::delete('/comics/{comic}/chapters/{chapter}', [ChapterController::class, 'destroy']);
    });

    // Админы
    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::put('/users/{user}/role', [UserController::class, 'updateRole']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});
