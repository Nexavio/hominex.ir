<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // از middleware های web مثل session یا csrf خبری نیست
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // می‌تونی هندلر سفارشی بزاری اینجا در آینده
    })->create();
