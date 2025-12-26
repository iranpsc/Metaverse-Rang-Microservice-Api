<?php

use App\Http\Controllers\Api\V2\LevelController;
use App\Http\Controllers\Api\V1\VideoCommentsController;
use App\Http\Controllers\Api\V1\TutorialController;
use App\Http\Controllers\Api\V2\MapsController;
use App\Http\Controllers\Api\V2\Feature\BuildFeatureController;
use App\Http\Controllers\Api\V2\CommentReplyController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::controller(TutorialController::class)->prefix('tutorials')->group(function () {
        Route::withoutMiddleware(['auth:sanctum', 'verified'])->group(function () {

            Route::name('categories.')->group(function () {
                Route::get('/categories', 'getCategories')->name('index');
                Route::get('/categories/{category:slug}', 'showCategory')->name('show');
                Route::get('/categories/{category:slug}/videos', 'showCategoryVideos')->name('videos');
            });

            Route::prefix('categories')->name('subcategories.')->group(function () {
                Route::get('/{category:slug}/{subCategory:slug}', 'showSubCategory')->name('show');
            });

            Route::get('/', [TutorialController::class, 'index'])->name('index');
            Route::get('/{video:slug}', [TutorialController::class, 'show'])->name('show');
            Route::post('/search', 'search')->name('search');
        });
        Route::post('/{video}/interactions', 'interactions');
    });

    Route::controller(BuildFeatureController::class)->prefix('features')->group(function () {
        Route::get('/{feature}/build/package', 'getBuildPackage');
        Route::post('/{feature}/build/{buildingModel:model_id}', 'buildFeature')
        ->middleware('account.security')
        ->withoutScopedBindings();
        Route::get('/{feature}/build/buildings', 'getBuildings');
        Route::put('/{feature}/build/buildings/{buildingModel:model_id}', 'updateBuilding')
        ->middleware('account.security');
        Route::delete('/{feature}/build/buildings/{buildingModel:model_id}', 'destroyBuilding')
        ->middleware('account.security');
    });

    Route::controller(VideoCommentsController::class)->prefix('tutorials')->group(function () {
        Route::get('/{video}/comments', 'index')->withoutMiddleware(['auth:sanctum', 'verified']);
        Route::post('/{video}/comments', 'store');
        Route::put('/{video}/comments/{comment}', 'update');
        Route::delete('/{video}/comments/{comment}', 'destroy');

        Route::post('/{video}/comments/{comment}/report', 'report');
        Route::post('/{video}/comments/{comment}/interactions', 'interactions');
    });

    Route::controller(CommentReplyController::class)->prefix('comments')->group(function () {
        // Reply routes
        Route::get('{comment}/replies', 'index')->withoutMiddleware(['auth:sanctum', 'verified']);
        Route::post('{comment}/reply', 'store');
        Route::put('{comment}/replies/{reply}', 'update');
        Route::delete('{comment}/replies/{reply}', 'destroy');
        Route::post('{comment}/replies/{reply}/interactions', 'interactions');
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

Route::get('/users/{user:email}/level', function (User $user) {
    return response()->json([
        'level' => $user->latest_level,
        'score' => $user->score,
    ]);
});
