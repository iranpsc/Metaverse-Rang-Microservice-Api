<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DynastyController;
use App\Http\Controllers\JoinRequestController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Feature\BuyRequestsController;
use App\Http\Controllers\Feature\SellRequestsController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ChildernPermissionsController;
use App\Http\Controllers\CustomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Feature\BuyFeatureController;
use App\Http\Controllers\Feature\FeatureController;
use App\Http\Controllers\Feature\FeatureHourlyProfitController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TicketController;
use Illuminate\Http\Request;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\ResetInfo\ResetEmailController;
use App\Http\Controllers\ResetInfo\ResetPhoneController;
use App\Http\Controllers\UserEventsController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

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


Route::middleware(['api', 'check.ip'])->group(function () {
    Route::controller(HomeController::class)->group(function () {
        Route::get('/home', 'index');
        Route::get('/get-user-info/{user}', 'showUserDetails');
        Route::get('/store', 'store');
    });

    Route::post('/register/{referral?}', [RegisterController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');

    Route::get('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return response()->json(['message' => 'لینک تایید حساب کاربری ارسال شد']);
    })->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');
});

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, '__invoke'])
    ->middleware(['signed'])->name('verification.verify');


Route::middleware(['auth:sanctum', 'api', 'verified', 'check.ip'])->group(function () {
    Route::controller(DashboardController::class)->group(function () {
        Route::get('/profile', 'index');
    });

    Route::controller(FeatureController::class)->scopeBindings()->prefix('my-features')->group(function () {
        Route::get('/', 'index');
        Route::get('/{user}/features/{feature}', 'show')
            ->missing(function () {
                return response()->json(['error' => 'ملک مورد نظر یافت نشد']);
            });
        Route::post('/{user}/addimage/{feature}', 'addFeatureImages')
            ->missing(function () {
                return response()->json(['error' => 'ملک متعلق به شما نمی باشد']);
            });
        Route::post('/otp/turn-off', 'turnOffOtp')->middleware('turnOff.otp');
        Route::get('/otp/turn-on', 'turnOnOtp');

        Route::post('/{user}/features/{feature}', 'updateFeature')
            ->missing(function () {
                return response()->json(['error' => 'ملک متعلق به شما نمی باشد']);
            });
    });

    Route::controller(OtpController::class)->prefix('otp')->group(function () {
        Route::post('/get-code', 'getOtpCode');
        Route::post('/verify-code', 'verifyOtpCode');
    });

    Route::middleware(['verified.phone', 'check.otp'])->group(function () {
        Route::controller(BuyFeatureController::class)->prefix('feature')->group(function () {
            Route::get('/{feature}', 'show')->withoutMiddleware(['verified.phone', 'check.otp', 'auth:sanctum', 'verified']);
            Route::post('/buy/{feature}', 'buy')
                ->middleware(['verified.phone', 'can:buy,feature'])->missing(function () {
                    return response()->json(['error' => 'ملک مورد نظر یافت نشد']);
                });
        });

        Route::controller(SellRequestsController::class)->prefix('sell-requests')->group(function () {
            Route::get('/', 'index')->withoutMiddleware('check.otp');
            Route::post('/store/{feature}', 'store')->can('sell', 'feature');
            Route::delete('/delete/{sellRequest}', 'destroy')->can('delete', 'sellRequest');
        });

        Route::controller(BuyRequestsController::class)->prefix('buy-requests')->group(function () {
            Route::get('/', 'index')->withoutMiddleware('check.otp');
            Route::get('/recieved', 'recievedBuyRequests')->withoutMiddleware('check.otp');
            Route::post('/buy/{feature}', 'buy')->can('buy', 'feature');
            Route::post('/store/{feature}', 'store')->can('sendBuyRequest', 'feature');
            Route::delete('/delete/{buyFeatureRequest}', 'destroy')->can('delete', 'buyFeatureRequest');
            Route::post('/accept/{buyFeatureRequest}', 'acceptBuyRequest')->can('accept', 'buyFeatureRequest');
            Route::post('/reject/{buyFeatureRequest}', 'rejectBuyRequest')->can('reject', 'buyFeatureRequest');
        });
    });


    Route::apiResource('reports', ReportController::class);

    Route::controller(FollowController::class)->group(function () {
        Route::get('/followers', 'followers');
        Route::get('/following', 'followings');
        Route::get('/follow/{user}', 'follow')->can('follow', 'user');
        Route::get('/unfollow/{user}', 'unfollow');
        Route::get('/remove/{user}', 'remove');
    });

    Route::controller(TicketController::class)->prefix('tickets')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/recieved', 'recieved');
        Route::get('/{ticket}', 'show');
        Route::post('/response/{ticket}', 'response')->can('respond', 'ticket');
        Route::delete('/{ticket}', 'destroy')->can('delete', 'ticket');
        Route::get('/close/{ticket}', 'close')->can('close', 'ticket');
    });

    Route::apiResource('notes', NoteController::class);

    Route::controller(KycController::class)->prefix('kyc')->group(function () {
        Route::get('/{kyc}', 'show')->can('view', 'kyc');
        Route::post('/', 'store')->can('create', 'App\\Models\Kyc');
        Route::put('/{kyc}', 'update')->can('update', 'kyc');
        Route::delete('/{kyc}', 'destroy')->can('delete', 'kyc');
    });

    Route::controller(SearchController::class)->group(function () {
        Route::post('search/users', 'users');
        Route::post('search/features', 'features');
    });

    Route::controller(SettingController::class)->group(function () {
        Route::post('/settings', 'update');
        Route::post('/general-settings', 'generalSettingsUpdate');
        Route::post('/settings/upload-profile-photo', 'uploadProfilePhoto');
        Route::post('/phone/send-otp', 'sendPhoneVerificationOtp');
        Route::post('/phone/verify', 'verifyPhone');
    });

    Route::post('/order', [OrderController::class, 'create']);

    Route::prefix('reset')->group(function () {
        Route::controller(ResetPhoneController::class)->prefix('phone')->group(function () {
            Route::post('/old/send-code', 'sendOtpToOldPhone');
            Route::post('/old/verify-code', 'verifyOldPhoneOtp');
            Route::post('/new/verify-code', 'verifyNewPhoneOtp');
        });
        Route::controller(ResetEmailController::class)->prefix('email')->group(function () {
            Route::post('/old/send-code', 'sendOtpToOldEmail');
            Route::post('/old/verify-code', 'verifyOldEmailOtp');
            Route::post('/new/verify-code', 'verifyNewEmailOtp');
        });
    });

    Route::controller(ResetPasswordController::class)->group(function () {
        Route::post('/reset-password/send-otp-code', 'sendOtpCode');
        Route::post('/reset-password', 'resetPassword');
    });


    Route::get('/online', function () {
    })->name('user-is-online');
    //    DYNASTY SECTION
    Route::prefix('/dynasty')->group(function () {

        Route::get('/create/{feature}', [DynastyController::class, 'store'])
            ->can('create', 'App\\Models\Dynasty\Dynasty');

        Route::post('/send-join-request', [JoinRequestController::class, 'store']);

        Route::post('/verify-otp', [JoinRequestController::class, 'verifyOtp']);
        Route::post('/resend-otp', [JoinRequestController::class, 'resendOtp']);
        Route::get('/accept-join-request/{joinRequest}/send-otp', [JoinRequestController::class, 'acceptRequest']);
        Route::post('/verify-accept-otp/{joinRequest}', [JoinRequestController::class, 'verifyAcceptOtp']);
        Route::post('/permission/{user}', [ChildernPermissionsController::class, 'update']);
        Route::post('/reject-join-request', [JoinRequestController::class, 'rejectRequest']);
        Route::patch('/change-dynasty-feature', [DynastyController::class, 'updateDynastyFeature']);
    });


    Route::controller(FeatureHourlyProfitController::class)->scopeBindings()->prefix('get-hourly-profits')->group(function () {
        Route::get('/{karbari?}', 'getHourlyProfits');
        Route::get('/{user}/features/{feature}', 'getHourlyProfit')->missing(function () {
            return response()->json([
                'error' => 'درخواست نا معتبر است'
            ]);
        });
    });

    Route::apiResource('customs', CustomController::class);

    Route::controller(UserEventsController::class)->prefix('events')->group(function () {
        Route::get('/', 'index');
        Route::post('/report/{userEvent}', 'store');
        Route::post('/report/response/{userEvent}', 'sendResponse');
        Route::get('/report/close/{userEvent}', 'closeEventReport');
    });
});

Route::any('/order/callback/{order}', [OrderController::class, 'callback'])->name('order.callback');
