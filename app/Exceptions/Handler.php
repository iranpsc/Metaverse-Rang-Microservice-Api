<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;


class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });


        $this->renderable(function (KycVerificationException $exception, Request $request) {
            return $request->expectsJson() ?
                abort(412, 'جهت ادامه فرایند لطفا احراز هویت خود را تکمیل نمایید') :
                redirect('/kyc');
        });

        $this->renderable(function (AccountSecurityException $exception, Request $request) {
            return $request->expectsJson() ?
                abort(410, 'جهت ادامه امنیت حساب کاربری خود را خاموش کنید') :
                redirect('/account-security');
        });

        $this->renderable(function (EmailVerificationException $exception, Request $request) {
            return $request->expectsJson() ?
                abort(411, 'جهت ادامه ایمیل خود را تایید نمایید') :
                to_route('verification.notice');
        });
    }
}
