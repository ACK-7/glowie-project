<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        // Always return JSON for API requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
                'error' => config('app.debug') ? get_class($e) : null,
            ], $this->getHttpStatusCode($e));
        }

        return parent::render($request, $e);
    }

    private function getHttpStatusCode(Throwable $e): int
    {
        return method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
    }
}

