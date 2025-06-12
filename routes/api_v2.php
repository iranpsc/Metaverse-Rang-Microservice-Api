<?php

use App\Http\Controllers\Api\V2\LevelController;
use App\Http\Controllers\Api\V1\VideoCommentsController;
use App\Http\Controllers\Api\V1\TutorialController;
use App\Http\Controllers\Api\V2\MapsController;
use App\Http\Controllers\Api\V2\Feature\BuildFeatureController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::controller(TutorialController::class)->prefix('tutorials')->as('tutorials.')->group(function () {
        Route::withoutMiddleware(['auth:sanctum', 'verified'])->group(function () {

            Route::name('categories.')->group(function () {
                Route::get('/categories', 'getCategories')->name('index');
                Route::get('/categories/{category:slug}', 'showCategory')->name('show');
            });

            Route::prefix('categories')->name('subcategories.')->group(function () {
                Route::get('/{category:slug}/{subCategory:slug}', 'showSubCategory')->name('show');
            });

            Route::get('/', [TutorialController::class, 'index'])->name('index');
            Route::get('/{video:slug}', [TutorialController::class, 'show'])->name('show');
            Route::post('/search', 'search')->name('search');
        });
        Route::post('/{video}/like', 'like');
        Route::post('/{video}/dislike', 'dislike');
    });

    Route::controller(VideoCommentsController::class)->prefix('tutorials')->group(function () {
        Route::get('/{video}/comments', 'index')->withoutMiddleware(['auth:sanctum', 'verified']);
        Route::post('/{video}/comments', 'store');
        Route::put('/{video}/comments/{comment}', 'update');
        Route::delete('/{video}/comments/{comment}', 'destroy');
        Route::post('/{video}/comments/{comment}/report', 'report');
        Route::post('/{video}/comments/{comment}/like', 'like');
        Route::post('/{video}/comments/{comment}/dislike', 'dislike');

        // Reply routes
        Route::post('/{video}/comments/{comment}/reply', 'storeReply');
        Route::get('/{video}/comments/{comment}/replies', 'getReplies')->withoutMiddleware(['auth:sanctum', 'verified']);
        Route::post('/{video}/comments/{comment}/replies/{reply}/like', 'likeReply');
        Route::post('/{video}/comments/{comment}/replies/{reply}/dislike', 'dislikeReply');
    });

    Route::controller(BuildFeatureController::class)->prefix('features')->group(function () {
        Route::get('/{feature}/build/package', 'getBuildPackage');
        Route::post('/{feature}/build/{buildingModel:model_id}', 'buildFeature')->withoutScopedBindings();
        Route::get('/{feature}/build/buildings', 'getBuildings');
        Route::put('/{feature}/build/buildings/{buildingModel:model_id}', 'updateBuilding');
        Route::delete('/{feature}/build/buildings/{buildingModel:model_id}', 'destroyBuilding');
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

Route::apiResource('maps', MapsController::class)->only(['index', 'show']);

Route::controller(MapsController::class)->prefix('maps')->as('maps.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{map}', 'show')->name('show');
    Route::get('/{map}/border', 'showBorder');
});
