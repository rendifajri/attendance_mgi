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
            /*$res = [];
            $code = 500;
            if ($e instanceof ValidationException) {
                $res = [
                    'status' => 'validation_error',
                    'message' => $e->errors()
                ];
                $code = 400;
            }
            else if ($e instanceof \GeneralException) {
                $res = [
                    'status' => $e->getMessage(),
                    'message' => $e->getData()
                ];
                $code = $e->getCode();
            }
            else if ($e instanceof AuthenticationException) {
                $res = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
                $code = 401;
            }
            return($res, $code);*/
            //var_dump($e instanceof NotFoundHttpException);
            //var_dump(get_class($e));
            //var_dump($e->getMessage());
            if ($e instanceof \AccessDeniedHttpException) {
                return response(['status' => 'access_denied', 'message' => $e->getMessage(), 'response' => null], 403);
            }
            else if ($e instanceof \AuthenticationException) {
                return response(['status' => 'unauthenticated', 'message' => $e->getMessage(), 'response' => null], 401);
            }
            else if ($e instanceof NotFoundHttpException) {
                return response(['status' => 'not_found', 'message' => $e->getMessage(), 'response' => null], 404);
            }
            else if ($e instanceof \ValidationException) {
                //var_dump(($e->validator->getData()));
                return response(['status' => 'validation_error', 'message' => $e->errors(), 'response' => $e->validator->getData()], 400);
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
