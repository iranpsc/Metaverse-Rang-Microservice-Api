<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    public function render($request, Throwable $e)
    {
        if($e instanceof ModelNotFoundException) {
            abort(404, 'Not Found!');
        }
        return parent::render($request, $e);
    }

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
                abort(412, 'Kyc is not completed yet!') :
                redirect('/kyc');
        });

        $this->renderable(function (AccountSecurityException $exception, Request $request) {
            return $request->expectsJson() ?
                abort(410, 'Wallet lock is on!') :
                redirect('/account-security');
        });

        $this->renderable(function (EmailVerificationException $exception, Request $request) {
            return $request->expectsJson() ?
                abort(411, 'Email is not verified!') :
                to_route('verification.notice');
        });

        $this->renderable(function (InsufficientBalanceException $exception, Request $request) {
            return $request->expectsJson() ?
                abort($exception->getCode(), $exception->getMessage()) :
                to_route('store');
        });
    }
}
