<?php

use App\Http\Controllers\Api\V2\LevelController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified', 'user.activity'])->group(function () {
    Route::controller(TutorialController::class)->prefix('tutorials')->as('tutorials.')->group(function () {
        Route::get('/', 'index')->withoutMiddleware(['auth:sanctum', 'verified'])->name('index');
        Route::get('/{video}', 'show')->withoutMiddleware(['auth:sanctum', 'verified'])->name('show');
        Route::post('/like/{video}', 'like');
        Route::post('/dislike/{video}', 'dislike');
        Route::post('/search', 'search')->withoutMiddleware(['auth:sanctum', 'verified']);
    });

    Route::controller(VideoCommentsController::class)->prefix('tutorials')->group(function () {
        Route::get('/{video}/comments', 'index')->withoutMiddleware(['auth:sanctum', 'verified']);
        Route::post('/{video}/comments', 'store');
        Route::put('/{video}/comments/{comment}', 'update');
        Route::delete('/{video}/comments/{comment}', 'destroy');
        Route::post('/{video}/comments/{comment}/report', 'report');
        Route::post('/{video}/comments/{comment}/like', 'like');
        Route::post('/{video}/comments/{comment}/dislike', 'dislike');
    });
});


Route::controller(LevelController::class)->prefix('levels')->group(function () {
    Route::get('/', 'index');
    Route::get('/{level}', 'show');
    Route::get('/{level}/general-info', 'getGeneralInfo');
    Route::get('/{level}/gem', 'gem');
    Route::get('/{level}/gift', 'gift');
    Route::get('/{level}/licenses', 'licenses');
    Route::get('/{level}/prize', 'prizes');
});
