<?php

use App\Http\Controllers\AccountSecurityController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\Challenge\QuestionController;
use App\Http\Controllers\CustomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dynasty\AcceptJoinRequestController;
use App\Http\Controllers\Dynasty\ChildernPermissionsController;
use App\Http\Controllers\Dynasty\DynastyController;
use App\Http\Controllers\Dynasty\DynastyPrizeController;
use App\Http\Controllers\Dynasty\FamilyController;
use App\Http\Controllers\Dynasty\SendJoinRequestController;
use App\Http\Controllers\Feature\BuyFeatureController;
use App\Http\Controllers\Feature\BuyRequestsController;
use App\Http\Controllers\Feature\FeatureController;
use App\Http\Controllers\Feature\FeatureHourlyProfitController;
use App\Http\Controllers\Feature\SellRequestsController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResetInfo\ResetEmailController;
use App\Http\Controllers\ResetInfo\ResetPhoneController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SystemVariableController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserEventsController;
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


Route::middleware(['api', 'check.ip'])->group(function () {
    Route::controller(HomeController::class)->group(function () {
        Route::get('/home', 'index');
        Route::get('/get-user-info/{user}', 'showUserDetails');
        Route::get('/store', 'store');
    });

    Route::controller(CalendarController::class)->prefix('calendar')->group(function () {
        Route::get('/', 'getEvents');
        Route::get('/{event}', 'getSingleEvent');
        Route::get('/{event}/like', 'like');
        Route::get('/{event}/dislike', 'dislike');
    });

    Route::post('/register', [RegisterController::class, 'register']);
    Route::controller(LoginController::class)->middleware('auth:sanctum')->group(function () {
        Route::post('/login', 'login')->withoutMiddleware('auth:sanctum');
        Route::post('/logout', 'logout');
    });

    Route::controller(ResetPasswordController::class)->middleware('guest')->group(function () {
        Route::post('/forgot-password', 'sendResetPasswordLink');
        Route::post('/forgot-password/reset/password', 'resetPassword');
    });

    Route::get('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return response()->json(['message' => 'لینک تایید حساب کاربری ارسال شد']);
    })->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');
});

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, '__invoke'])
    ->middleware(['signed'])->name('verification.verify');


Route::middleware(['auth:sanctum', 'api', 'verified', 'check.ip', 'user.activity'])->group(function () {
    Route::controller(DashboardController::class)->prefix('user')->group(function () {
        Route::get('/profile', 'index');
        Route::get('/payments/latest', 'getUserLatestTransaction');
        Route::get('/transactions', 'transactions');
        Route::get('/privacy', 'getPrivacySettings');
        Route::post('/privacy', 'updatePrivacySettings');
    });

    Route::controller(AccountSecurityController::class)->group(function () {
        Route::post('/account/security', 'getVerifyCode');
        Route::post('account/security/verify', 'turnOffAccountSecurity');
    });

    Route::controller(FeatureController::class)->middleware('account.security')->scopeBindings()->group(function () {
        Route::prefix('my-features')->group(function () {
            Route::withoutMiddleware('account.security')->group(function () {
                Route::get('/', 'index');
                Route::get('/{user}/features/{feature}', 'show')
                    ->missing(function () {
                        return response()->json(['error' => 'ملک مورد نظر یافت نشد']);
                    });
            });
            Route::post('/{user}/add-image/{feature}', 'addFeatureImages')
                ->missing(function () {
                    return response()->json(['error' => 'ملک متعلق به شما نمی باشد']);
                });
            Route::post('/{user}/remove-image/{feature}/image/{image}', 'removeّFeatureImage')
                ->missing(function () {
                    return response()->json(['error' => 'ملک متعلق به شما نمی باشد']);
                });

            Route::post('/{user}/features/{feature}', 'updateFeature')
                ->missing(function () {
                    return response()->json(['error' => 'ملک متعلق به شما نمی باشد']);
                });
        });
        Route::controller(BuyFeatureController::class)->prefix('features')->group(function () {
            Route::get('/{feature}', 'show')->withoutMiddleware(['account.security', 'auth:sanctum', 'verified']);
            Route::post('/buy/{feature}', 'buy')
                ->middleware('can:buy,feature')->missing(function () {
                    return response()->json(['error' => 'ملک مورد نظر یافت نشد']);
                });
        });

        Route::controller(SellRequestsController::class)->prefix('sell-requests')->group(function () {
            Route::get('/', 'index')->withoutMiddleware('account.security');
            Route::post('/store/{feature}', 'store')->can('sell', 'feature');
            Route::delete('/delete/{sellRequest}', 'destroy')->can('delete', 'sellRequest');
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
    Route::apiResource('kyc', KycController::class);
    Route::apiResource('bank-accounts', BankAccountController::class);

    Route::controller(SearchController::class)->group(function () {
        Route::post('search/users', 'users');
        Route::post('search/features', 'features');
    });
    Route::post('/order', [OrderController::class, 'create']);

    Route::prefix('reset')->middleware('account.security')->group(function () {
        Route::controller(ResetPhoneController::class)->prefix('phone')->group(function () {
            Route::post('/', 'sendVerifyCode');
            Route::post('/verify', 'verify');
        });
        Route::controller(ResetEmailController::class)->prefix('email')->group(function () {
            Route::post('/', 'sendVerifyCode');
            Route::post('/verify', 'verify');
        });
        Route::post('/password', ChangePasswordController::class);
    });

    //    DYNASTY SECTION
    Route::prefix('/dynasty')->group(function () {
        Route::controller(DynastyController::class)->group(function () {
            Route::get('/', 'index');
            Route::middleware('account.security')->group(function() {
                Route::post('/create/{feature}', 'store')->can('create', 'App\\Models\Dynasty\Dynasty');
                Route::post('/{dynasty}/update/{feature}', 'updateDynastyFeature')
                    ->can('updateDynastyFeature', ['dynasty', 'feature']);
                Route::post('/{dynasty}/update/{feature}', 'verifyUpdateDynastyFeature')
                    ->can('updateDynastyFeature', ['dynasty', 'feature']);
                Route::post('/{dynasty}/update/{feature}/resend/otp', 'resendOtp')
                    ->can('updateDynastyFeature', ['dynasty', 'feature']);
            });
        });

        Route::controller(FamilyController::class)->group(function() {
            Route::get('/{dynasty}/family/{family}', 'index')
            ->missing(function() {
                return response()->json(['error' => 'درخواست معتبر نمی باشد.'], 404);
            });
        });
        Route::controller(SendJoinRequestController::class)->scopeBindings()->group(function () {
            Route::get('/requests/sent', 'index');
            Route::get('/requests/sent/{user}/show/{sentJoinRequest}', 'show')
                ->missing(function () {
                    return response()->json(['error' => 'درخواست معتبر نمی باشد.'], 404);
                });
            Route::post('/add/member/get/permissions', 'getPermissions');
            Route::post('/add/member', 'store');
            Route::post('/add/member/{user}/verify/{sentJoinRequest}', 'verify')
                ->missing(function () {
                    return response()->json(['error' => 'درخواست معتبر نمی باشد'], 404);
                });
            Route::get('/add/member/{user}/verify/{sentJoinRequest}/resend/otp', 'resendOtp')
                ->missing(function () {
                    return response()->json(['error' => 'درخواست معتبر نمی باشد.'], 404);
                });
        });

        Route::controller(AcceptJoinRequestController::class)->scopeBindings()->group(function () {
            Route::get('/requests/recieved', 'index');
            Route::get('/requests/recieved/{user}/show/{recievedJoinRequest}', 'show')
                ->missing(function () {
                    return response()->json(['error' => 'درخواست یافت نشد'], 404);
                });
            Route::post('/requests/recieved/{user}/accept/{recievedJoinRequest}', 'accept')
                ->missing(function () {
                    return response()->json(['error' => 'درخواست یافت نشد'], 404);
                });
            Route::post('/requests/recieved/{user}/verify/{recievedJoinRequest}', 'verify')
                ->missing(function () {
                    return response()->json(['error' => 'درخواست معتبر نمی باشد'], 404);
                });
            Route::post('/requests/recieved/{user}/verify/{recievedJoinRequest}/resend/otp', 'resendOtp')
                ->missing(function () {
                    return response()->json(['error' => 'درخواست معتبر نمی باشد.'], 404);
                });
            Route::post('/requests/recieved/{user}/reject/{recievedJoinRequest}', 'reject')
                ->missing(function () {
                    return response()->json(['error' => 'درخواست یافت نشد'], 404);
                });

            Route::controller(DynastyPrizeController::class)->scopeBindings()->group(function () {
                Route::get('/prizes', 'index');
                Route::get('/prizes/{recievedDynastyPrize}', 'show');
                Route::get('/prizes/{user}/get/{recievedDynastyPrize}', 'getPrize')
                    ->missing(function () {
                        return response()->json(['error' => 'یافت نشد.'], 404);
                    });
            });
        });
        Route::controller(ChildernPermissionsController::class)->group(function () {
            Route::post('/children/{user}', 'updatePermissions')->can('updatePermissions', 'user');
        });
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

    Route::get('/ping', function () {
    })->withoutMiddleware(['auth:sanctum', 'verified', 'check.ip']);

    Route::get('/notification-read/{notification}', function (Notification $notification) {
        $notification->update(['read_at' => now()]);
    });
});

Route::any('/order/callback/{order}', [OrderController::class, 'callback'])->name('order.callback');

Route::controller(PublicProfileController::class)->prefix('citizen')->group(function () {
    Route::get('/{code}', 'home');
});


Route::prefix('/challenge')->middleware(['api', 'auth:sanctum', 'check.ip'])->group(function () {
    Route::get('/timings', [SystemVariableController::class, 'index']);
    Route::get('/question', [QuestionController::class, 'index']);
    Route::post('/{question}/answer/{questionAnswer}', [QuestionController::class, 'answerQuestion']);
    Route::get('/truncate-users', function () {
        \App\Models\UserQuestionAnswer::truncate();
        return response()->json([
            'message' => 'done'
        ]);
    });
});
