<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        ]);

        // // Rate limiting configuration
        // $middleware->throttleApi('otp:3,60'); // 3 requests per minute
    })

    ->withCommands([
        App\Console\Commands\TestSmsCommand::class,
    ])

    ->withExceptions(function (Exceptions $exceptions) {
        // Custom exception handling can be added here
    })->create();
