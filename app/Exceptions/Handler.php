<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
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
        $this->renderable(function (Throwable $e) {
            if ($e instanceof \AccessDeniedHttpException) {
                return response()->json(['status' => 'access_denied', 'message' => $e->getMessage(), 'response' => null], 403, ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_UNESCAPED_UNICODE);
            }
            else if ($e instanceof \AuthenticationException) {
                return response()->json(['status' => 'unauthenticated', 'message' => $e->getMessage(), 'response' => null], 401, ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_UNESCAPED_UNICODE);
            }
            else if ($e instanceof NotFoundHttpException) {
                return response()->json(['status' => 'not_found', 'message' => $e->getMessage(), 'response' => null], 404, ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_UNESCAPED_UNICODE);
            }
            else if ($e instanceof \ValidationException) {
                return response()->json(['status' => 'validation_error', 'message' => $e->errors(), 'response' => $e->validator->getData()], 400, ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_UNESCAPED_UNICODE);
            }
            /*else {
                return response(['status' => 'undefined', 'message' => $e->getMessage(), 'response' => null], $e->getStatusCode());
            }*/
        });
    }
    /*protected function unauthenticated($request, AuthenticationException $exception)
    {
        // dd($exception);
        return response(['status' => 'unauthenticated', 'message' => 'Please login first.'], 401);
    }*/
}
