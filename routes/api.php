<?php

use App\Http\Controllers\Api\V1\AccountSecurityController;
use App\Http\Controllers\Api\V1\Auth\ChangePasswordController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\BankAccountController;
use App\Http\Controllers\Api\V1\CalendarController;
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
use App\Http\Controllers\Api\V1\PublicProfileController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ResetInfo\ResetEmailController;
use App\Http\Controllers\Api\V1\ResetInfo\ResetPhoneController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\UserEventsController;
use App\Models\ContestParticipants;
use App\Models\Notification;
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

Route::middleware('guest')->group(function () {
    Route::post('register', [RegisterController::class, 'register']);
    Route::post('login', [LoginController::class, 'login']);
});

Route::post('logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');

Route::controller(ResetPasswordController::class)
    ->prefix('forgot-password')
    ->middleware('guest')
    ->group(function () {
        Route::post('/', 'sendResetPasswordLink');
        Route::post('reset/password', 'resetPassword');
    });

Route::controller(CalendarController::class)->prefix('calendar')->group(function () {
    Route::get('/', 'getEvents');
    Route::get('/{event}', 'getSingleEvent');
    Route::get('/{event}/like', 'like');
    Route::get('/{event}/dislike', 'dislike');
});

Route::apiResource('players', PlayerController::class);

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/get-user-info/{user}', [HomeController::class, 'showUserDetails'])->name('top-player-details');

Route::middleware(['auth:sanctum', 'verified', 'user.activity'])->group(function () {

    Route::get('/store', [HomeController::class, 'store'])->name('store');

    Route::controller(DashboardController::class)->prefix('user')->group(function () {
        Route::get('/profile', 'index');
        Route::get('/payments/latest', 'getUserLatestTransaction');
        Route::get('/transactions', 'transactions');
        Route::get('/privacy', 'getPrivacySettings');
        Route::post('/privacy', 'updatePrivacySettings');
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

    Route::controller(AccountSecurityController::class)
        ->prefix('account/security')
        ->group(function () {
            Route::post('/', 'getVerifyCode');
            Route::post('verify', 'turnOffAccountSecurity');
        });

    Route::controller(FeatureController::class)->middleware('account.security')
        ->scopeBindings()->group(function () {
            Route::prefix('my-features')->group(function () {
                Route::withoutMiddleware('account.security')->group(function () {
                    Route::get('/', 'index');
                    Route::get('/{user}/features/{feature}', 'show');
                });
                Route::post('/{user}/add-image/{feature}', 'addFeatureImages');
                Route::post('/{user}/remove-image/{feature}/image/{image}', 'removeّFeatureImage');

                Route::post('/{user}/features/{feature}', 'updateFeature');
            });
            Route::controller(BuyFeatureController::class)->prefix('features')->group(function () {
                Route::get('/{feature}', 'show')->withoutMiddleware(['account.security', 'auth:sanctum', 'verified']);
                Route::post('/buy/{feature}', 'buy')->can('buy', 'feature');
            });

            Route::controller(SellRequestsController::class)->prefix('sell-requests')->group(function () {
                Route::get('/', 'index')->withoutMiddleware('account.security');
                Route::post('/store/{feature}', 'store')->can('sell', 'feature');
                Route::delete('/{sellRequest}', 'destroy')->can('delete', 'sellRequest');
            });

            Route::controller(BuyRequestsController::class)->prefix('buy-requests')->group(function () {
                Route::withoutMiddleware('account.security')->group(function () {
                    Route::get('/', 'index');
                    Route::get('/recieved', 'recievedBuyRequests');
                });
                Route::post('/store/{feature}', 'store')->can('sendBuyRequest', 'feature');
                Route::delete('/delete/{buyFeatureRequest}', 'destroy')->can('delete', 'buyFeatureRequest');
                Route::post('/accept/{buyFeatureRequest}', 'acceptBuyRequest')->can('accept', 'buyFeatureRequest');
                Route::post('/reject/{buyFeatureRequest}', 'rejectBuyRequest')->can('reject', 'buyFeatureRequest');
            });
        });

    Route::controller(SettingController::class)->group(function () {
        Route::post('/settings', 'update');
        Route::post('/general-settings', 'generalSettingsUpdate');
        Route::post('/settings/upload-profile-photo', 'uploadProfilePhoto');
    });

    Route::apiResource('reports', ReportController::class);

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
        Route::post('/response/{ticket}', 'response');
        Route::get('/close/{ticket}', 'close');
    });

    Route::apiResource('tickets', TicketController::class);

    Route::apiResource('notes', NoteController::class);
    Route::apiResource('kyc', KycController::class);
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
    Route::prefix('/dynasty')->group(function () {
        Route::controller(DynastyController::class)->group(function () {
            Route::get('/', 'index');
            Route::middleware('account.security')->group(function () {
                Route::post('/create/{feature}', 'store');
                Route::post('/{dynasty}/update/{feature}', 'updateDynastyFeature');
                Route::post('/{dynasty}/update/{feature}/verify', 'verifyUpdateDynastyFeature');
                Route::post('/{dynasty}/update/{feature}/resend/otp', 'resendOtp');
            });
        });

        Route::get('/{dynasty}/family/{family}', [FamilyController::class, 'index']);

        Route::controller(SendJoinRequestController::class)->scopeBindings()->group(function () {
            Route::get('/requests/sent', 'index');
            Route::get('/requests/sent/{user}/show/{sentJoinRequest}', 'show');
            Route::post('/add/member/get/permissions', 'getPermissions');
            Route::post('/add/member', 'store');
            Route::post('/add/member/{user}/verify/{sentJoinRequest}', 'verify');
            Route::get('/add/member/{user}/verify/{sentJoinRequest}/resend/otp', 'resendOtp');
            Route::post('/search', 'search');
        });

        Route::controller(AcceptJoinRequestController::class)->scopeBindings()
            ->prefix('requests')
            ->group(function () {
                Route::get('/recieved', 'index');
                Route::get('/recieved/{user}/show/{recievedJoinRequest}', 'show');
                Route::post('/recieved/{user}/accept/{recievedJoinRequest}', 'accept');
                Route::post('/recieved/{user}/verify/{recievedJoinRequest}', 'verify');
                Route::post('/recieved/{user}/verify/{recievedJoinRequest}/resend/otp', 'resendOtp');
                Route::post('/recieved/{user}/reject/{recievedJoinRequest}', 'reject');
            });

        Route::controller(DynastyPrizeController::class)->scopeBindings()->group(function () {
            Route::get('/prizes', 'index');
            Route::get('/prizes/{recievedDynastyPrize}', 'show');
            Route::get('/prizes/{user}/get/{recievedDynastyPrize}', 'getPrize');
        });

        Route::post('/children/{user}', ChildernPermissionsController::class);
    });

    Route::controller(FeatureHourlyProfitController::class)
        ->scopeBindings()
        ->prefix('hourly-profits')
        ->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'getProfits');
            Route::get('/{user}/features/{feature}', 'getProfit');
        });

    Route::apiResource('customs', CustomController::class);

    Route::controller(UserEventsController::class)->prefix('events')->group(function () {
        Route::get('/', 'index');
        Route::post('/report/{userEvent}', 'store');
        Route::post('/report/response/{userEvent}', 'sendResponse');
        Route::get('/report/close/{userEvent}', 'closeEventReport');
    });

    Route::get('/notification-read/{notification}', function (Notification $notification) {
        $notification->update(['read_at' => now()]);
    });
});

Route::post('video-tutorials', [HomeController::class, 'getTutorials']);

Route::get('ping', static fn () => null);

Route::any('/order/callback/{order}', [OrderController::class, 'callback'])
    ->name('order.callback');

Route::controller(PublicProfileController::class)
    ->prefix('citizen')->group(function () {
        Route::get('/{code}', 'home');
    });

Route::post('/send-mail', function (Request $request) {
    $request->validate([
        'fname' => 'required',
        'lname' => 'required',
        'email' => 'required|email',
        'phone' => 'required|ir_mobile',
        'code' => 'required|string',
        'description' => 'required|string|max:1500',
    ]);

    ContestParticipants::create($request->only([
        'fname',
        'lname',
        'email',
        'phone',
        'code',
        'description'
    ]));

    return response()->noContent(201);
});
