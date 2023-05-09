<?php

use App\Http\Controllers\Api\V2\LevelController;
use App\Http\Controllers\Api\V1\VideoCommentsController;
use App\Http\Controllers\Api\V1\TutorialController;
use App\Http\Controllers\Api\V2\VideoPanelController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::controller(TutorialController::class)->prefix('tutorials')->as('tutorials.')->group(function () {
        Route::withoutMiddleware(['auth:sanctum', 'verified'])->group(function () {
            Route::get('/', 'index')->name('index');

            Route::name('categories.')->group(function () {
                Route::get('/categories', 'categories')->name('index');
                Route::get('/categories/{category}', 'category')->name('show');
            });

            Route::prefix('categories')->name('subcategories.')->group(function () {
                Route::get('/{category}/subcategories', 'subcategories')->name('index');
                Route::get('/{category}/subcategories/{subCategory}', 'subcategory')->name('show');
                Route::get('/{category}/subcategories/{subCategory}/videos', 'subCategoryVideos')->name('videos');
            });

            Route::get('/{video}', 'show')->name('show');
            Route::post('/search', 'search');
        });
        Route::post('/like/{video}', 'like');
        Route::post('/dislike/{video}', 'dislike');
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
    Route::get('/{level:slug}', 'show');
    Route::get('/{level:slug}/general-info', 'getGeneralInfo');
    Route::get('/{level:slug}/gem', 'gem');
    Route::get('/{level:slug}/gift', 'gift');
    Route::get('/{level:slug}/licenses', 'licenses');
    Route::get('/{level:slug}/prize', 'prizes');
});


Route::controller(VideoPanelController::class)->group(function () {
});
