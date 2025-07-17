<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware
        $middleware->api(prepend: [
            \App\Http\Middleware\LogApiRequests::class,
        ]);

        // Route middleware aliases
        $middleware->alias([
            'log.api.requests' => \App\Http\Middleware\LogApiRequests::class,
            'check.otp.limit' => \App\Http\Middleware\CheckOtpLimit::class,
            'check.property.owner' => \App\Http\Middleware\CheckPropertyOwner::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'api.auth' => \App\Http\Middleware\ApiAuthenticate::class,
        ]);

        // Configure authentication redirects
        $middleware->redirectGuestsTo(function () {
            return response()->json([
                'success' => false,
                'message' => 'احراز هویت ناموفق - لطفا ابتدا وارد شوید.',
                'timestamp' => now()->toISOString()
            ], 401);
        });
    })

    ->withCommands([
        App\Console\Commands\TestSmsCommand::class,
    ])

    ->withExceptions(function (Exceptions $exceptions) {
        // Handle authentication errors
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'احراز هویت ناموفق - لطفا ابتدا وارد شوید.',
                    'timestamp' => now()->toISOString()
                ], 401);
            }
        });

        // Handle JWT exceptions
        $exceptions->render(function (TokenExpiredException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'توکن منقضی شده است.',
                    'timestamp' => now()->toISOString()
                ], 401);
            }
        });

        $exceptions->render(function (TokenInvalidException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'توکن نامعتبر است.',
                    'timestamp' => now()->toISOString()
                ], 401);
            }
        });

        $exceptions->render(function (JWTException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'توکن ارائه نشده است.',
                    'timestamp' => now()->toISOString()
                ], 401);
            }
        });

        // Handle 404 for API routes
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'آدرس مورد نظر یافت نشد.',
                    'timestamp' => now()->toISOString()
                ], 404);
            }
        });

        // Handle general exceptions for API routes
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') && config('app.debug') === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطای داخلی سرور.',
                    'timestamp' => now()->toISOString()
                ], 500);
            }
        });
    })->create();
