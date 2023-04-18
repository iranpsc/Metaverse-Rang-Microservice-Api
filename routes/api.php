<?php

use App\Http\Controllers\Api\V1\AccountSecurityController;
use App\Http\Controllers\Api\V1\Auth\ChangePasswordController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\SendResetPasswordLinkEmailController;
use App\Http\Controllers\Api\V1\BankAccountController;
use App\Http\Controllers\Api\V1\CalendarController;
use App\Http\Controllers\Api\V1\ChallengeController;
use App\Http\Controllers\Api\V1\CustomController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\Dynasty\AcceptJoinRequestController;
use App\Http\Controllers\Api\V1\Dynasty\ChildernPermissionsController;
use App\Http\Controllers\Api\V1\Dynasty\DynastyController;
use App\Http\Controllers\Api\V1\Dynasty\DynastyPrizeController;
use App\Http\Controllers\Api\V1\Dynasty\FamilyController;
use App\Http\Controllers\Api\V1\Dynasty\SendJoinRequestController;
use App\Http\Controllers\Api\V1\Feature\BuyFeatureController;
use App\Http\Controllers\Api\V1\Feature\BuyRequestsController;
use App\Http\Controllers\Api\V1\Feature\FeatureController;
use App\Http\Controllers\Api\V1\Feature\FeatureHourlyProfitController;
use App\Http\Controllers\Api\V1\Feature\SellRequestsController;
use App\Http\Controllers\Api\V1\FollowController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\KycController;
use App\Http\Controllers\Api\V1\NoteController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PlayerController;
use App\Http\Controllers\Api\V1\ProfilePhotoController;
use App\Http\Controllers\Api\V1\PublicProfileController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ResetInfo\ResetEmailController;
use App\Http\Controllers\Api\V1\ResetInfo\ResetPhoneController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TutorialController;
use App\Http\Controllers\Api\V1\UserEventsController;
use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('register', [RegisterController::class, 'register'])->middleware('guest');
Route::post('login', [LoginController::class, 'login'])->middleware('guest');

Route::post('logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('guest')->group(function () {
    Route::post('/forgot-password', [SendResetPasswordLinkEmailController::class, 'sendResetLinkEmail']);
    Route::post('/forgot-password/reset/password', [ResetPasswordController::class, 'reset']);
});

Route::controller(CalendarController::class)->prefix('calendar')->group(function () {
    Route::get('/', 'getEvents');
    Route::get('/{event}', 'getSingleEvent');
    Route::get('/{event}/like', 'like');
    Route::get('/{event}/dislike', 'dislike');
});

Route::controller(PlayerController::class)->prefix('players')->group(function () {
    Route::get('/', 'index');
    Route::get('/{player}/profile', 'profile');
    Route::get('/{player}/assets', 'assets')->name('players.features');
    Route::get('/{player}/assets/{feature}', 'asset')->name('players.feature');
    Route::get('/{player}/followers', 'followers');
    Route::get('/{player}/following', 'following');
});

Route::middleware(['auth:sanctum', 'verified', 'user.activity'])->group(function () {

    Route::controller(HomeController::class)->group(function () {
        Route::post('/store', 'store');
    });

    Route::controller(DashboardController::class)->prefix('user')->group(function () {
        Route::get('/profile', 'index');
        Route::get('/wallet', 'showWallet');
        Route::get('/transactions', 'transactions');
        Route::get('/payments/latest', 'latestTransaction');
    });

    Route::controller(EmailVerificationController::class)->prefix('email')->group(function () {
        Route::get('/verify/{id}/{hash}', 'verify')
            ->withoutMiddleware(['auth:sanctum', 'verified'])
            ->middleware(['signed'])
            ->name('verification.verify');
        Route::get('/verification-notification', function (Request $request) {
            $request->user()->sendEmailVerificationNotification();
        })
            ->withoutMiddleware('verified')
            ->middleware('throttle:6,1')
            ->name('verification.send');
    });

    Route::controller(AccountSecurityController::class)->prefix('account/security')->group(function () {
        Route::post('/', 'getVerifyCode');
        Route::post('verify', 'turnOffAccountSecurity');
    });

    Route::scopeBindings()->group(function () {
        Route::controller(FeatureController::class)->as('my-features.')->prefix('my-features')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{user}/features/{feature}', 'show')->name('show');
            Route::post('/{user}/add-image/{feature}', 'addFeatureImages');
            Route::post('/{user}/remove-image/{feature}/image/{image}', 'removeّFeatureImage');
            Route::post('/{user}/features/{feature}', 'updateFeature');
        });

        Route::controller(BuyFeatureController::class)->middleware('account.security')->prefix('features')->group(function () {
            Route::withoutMiddleware(['account.security', 'verified', 'auth:sanctum'])->group(function () {
                Route::get('/', 'index')->name('features');
                Route::get('/{feature}', 'show')->name('features.show');
            });
            Route::post('/buy/{feature}', 'buy')->can('buy', 'feature');
        });

        Route::controller(SellRequestsController::class)->prefix('sell-requests')->group(function () {
            Route::get('/', 'index');
            Route::post('/store/{feature}', 'store')->can('sell', 'feature');
            Route::delete('/{sellRequest}', 'destroy')->can('delete', 'sellRequest');
        });

        Route::controller(BuyRequestsController::class)->prefix('buy-requests')->group(function () {
            Route::get('/', 'index');
            Route::get('/recieved', 'recievedBuyRequests');
            Route::post('/store/{feature}', 'store')->can('sendBuyRequest', 'feature');
            Route::delete('/delete/{buyFeatureRequest}', 'destroy')->can('delete', 'buyFeatureRequest');
            Route::post('/accept/{buyFeatureRequest}', 'acceptBuyRequest')->can('accept', 'buyFeatureRequest');
            Route::post('/reject/{buyFeatureRequest}', 'rejectBuyRequest')->can('reject', 'buyFeatureRequest');
        });
    });

    Route::controller(SettingController::class)->group(function () {
        Route::get('/settings', 'showSettings');
        Route::post('/settings', 'update');
        Route::get('/general-settings', 'showGeneralSettings');
        Route::put('/general-settings/{generalSetting}', 'updateGeneralSettings');
        Route::get('/privacy', 'getPrivacySettings');
        Route::post('/privacy', 'updatePrivacySettings');
    });

    Route::apiResource('profilePhotos', ProfilePhotoController::class);

    Route::apiResource('reports', ReportController::class)->only(['index', 'show', 'store']);

    Route::controller(FollowController::class)->group(function () {
        Route::get('/followers', 'followers');
        Route::get('/following', 'followings');
        Route::get('/follow/{user}', 'follow');
        Route::get('/unfollow/{user}', 'unfollow');
        Route::get('/remove/{user}', 'remove');
    });

    Route::controller(TicketController::class)->prefix('tickets')->group(function () {
        Route::get('/recieved', 'recieved');
        Route::get('/recieved/{ticket}', 'view');
        Route::post('/response/{ticket}', 'response')->name('tickets.response');
        Route::get('/close/{ticket}', 'close');
    });

    Route::apiResource('tickets', TicketController::class);
    Route::apiResource('notes', NoteController::class);
    Route::apiResource('kyc', KycController::class)->only(['index', 'store', 'update']);
    Route::apiResource('bank-accounts', BankAccountController::class);

    Route::controller(SearchController::class)->prefix('search')->group(function () {
        Route::post('/users', 'users');
        Route::post('/features', 'features');
    });

    Route::post('order', [OrderController::class, 'store']);

    Route::prefix('reset')->as('reset.')->middleware('account.security')->group(function () {
        Route::controller(ResetPhoneController::class)->prefix('phone')->group(function () {
            Route::post('/', 'sendVerifyCode')->name('phone');
            Route::post('/verify', 'verify');
        });
        Route::controller(ResetEmailController::class)->prefix('email')->group(function () {
            Route::post('/', 'sendVerifyCode')->name('email');
            Route::post('/verify', 'verify');
        });
        Route::post('/password', ChangePasswordController::class);
    });

    //    DYNASTY SECTION
    Route::prefix('dynasty')->group(function () {
        Route::controller(DynastyController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('create/{feature}', 'store');
            Route::post('/{dynasty}/update/{feature}', 'update');
        });

        Route::get('/{dynasty}/family/{family}', [FamilyController::class, 'index']);

        Route::controller(SendJoinRequestController::class)->group(function () {
            Route::get('/requests/sent', 'index');
            Route::get('/requests/sent/{joinRequest}', 'show')->name('dynasty.requests.sent.show');
            Route::post('/add/member/get/permissions', 'getPermissions');
            Route::post('/add/member', 'store');
            Route::delete('/requests/sent/{joinRequest}', 'destroy');
            Route::post('/search', 'search');
        });

        Route::controller(AcceptJoinRequestController::class)->as('joinRequests.')->prefix('requests/recieved')->group(function () {
            Route::get('/', 'index')->name('recieved.index');
            Route::get('/{joinRequest}', 'show')->name('recieved.show');
            Route::post('/{joinRequest}', 'accept');
            Route::delete('/{joinRequest}', 'reject');
        });

        Route::controller(DynastyPrizeController::class)->as('prizes.')->prefix('prizes')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{recievedPrize}', 'show')->name('show');
            Route::post('/{recievedPrize}', 'store');
        });

        Route::post('/children/{user}', ChildernPermissionsController::class);
    });

    Route::controller(FeatureHourlyProfitController::class)->prefix('hourly-profits')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'getProfitsByApplication');
        Route::post('/{featureHourlyProfit}', 'getSingleProfit');
    });

    Route::apiResource('customs', CustomController::class);

    Route::controller(UserEventsController::class)->as('user-events.')->prefix('events')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{userEvent}', 'show')->name('show');
        Route::post('/report/{userEvent}', 'store');
        Route::post('/report/response/{userEvent}', 'sendResponse');
        Route::post('/report/close/{userEvent}', 'closeEventReport');
    });

    Route::apiResource('notifications', NotificationController::class)->only(['index', 'show']);

    Route::controller(ChallengeController::class)->as('challenge.')->prefix('challenge')->group(function () {
        Route::get('timings', 'getTimings')->name('timing');
        Route::post('question', 'getQuestion')->name('question');
        Route::post('answer', 'answerResult')->name('answer');
    });
});

Route::controller(TutorialController::class)->prefix('tutorials')->group(function () {
    Route::get('/', 'index');
    Route::get('/{video}', 'show');
    Route::post('/like/{video}', 'like');
    Route::post('/dislike/{video}', 'dislike');
    Route::post('/search', 'search');
});

Route::get('ping', static fn () => null);

Route::any('/order/callback/{order}', [OrderController::class, 'callback'])->name('order.callback');

Route::controller(PublicProfileController::class)->prefix('citizen')->group(function () {
    Route::get('/{code}', 'home');
});
