<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($request->is('api/*') && !($exception instanceof NotFoundHttpException) && !($exception instanceof ModelNotFoundException) && !($exception instanceof HttpExceptionInterface)) {
            Log::error('API exception', [
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'error_type' => class_basename($exception),
            ], 500);
        }

        if ($exception instanceof NotFoundHttpException) {
            Log::error('404 occurred');
            return response()->json([
                'message' => trans('message.not_found', [], App::getLocale())
            ], 404);
        }

        if ($exception instanceof ModelNotFoundException) {
            Log::error('Model not found');
            return response()->json([
                'message' => trans('message.not_found', [], App::getLocale())
            ], 404);
        }

        return parent::render($request, $exception);
    }
}
