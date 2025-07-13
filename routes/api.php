<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\OtpController;
use App\Http\Controllers\Api\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Auth Routes
Route::prefix('auth')->group(function () {

    // Public routes (بدون احراز هویت)
    Route::post('register', [RegisterController::class, 'register']);
    Route::post('verify-registration', [RegisterController::class, 'verifyOtp']);
    Route::post('login', [LoginController::class, 'login']);
    Route::post('verify-login', [LoginController::class, 'verifyLoginOtp']);

    // OTP routes with rate limiting
    Route::middleware(['throttle:otp'])->group(function () {
        Route::post('send-otp', [OtpController::class, 'send']);
    });

    // Protected routes (نیاز به احراز هویت)
    Route::middleware(['auth:api'])->group(function () {
        Route::post('logout', [LoginController::class, 'logout']);
        Route::post('refresh', [LoginController::class, 'refresh']);
        Route::get('me', [LoginController::class, 'me']);
    });
});

// API Version 1
Route::prefix('v1')->middleware(['api', 'log.api.requests'])->group(function () {

    // Public routes
    Route::get('health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0'
        ]);
    });

    // Protected routes
    Route::middleware(['auth:api'])->group(function () {

        // User routes
        Route::prefix('user')->group(function () {
            Route::get('profile', [App\Http\Controllers\Api\User\ProfileController::class, 'show']);
            Route::put('profile', [App\Http\Controllers\Api\User\ProfileController::class, 'update']);
        });

        // Property routes
        Route::prefix('properties')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Property\PropertyController::class, 'index']);
            Route::get('/{property}', [App\Http\Controllers\Api\Property\PropertyController::class, 'show']);

            // Favorites
            Route::post('/{property}/favorite', [App\Http\Controllers\Api\Property\FavoriteController::class, 'store']);
            Route::delete('/{property}/favorite', [App\Http\Controllers\Api\Property\FavoriteController::class, 'destroy']);
            Route::get('/favorites/list', [App\Http\Controllers\Api\Property\FavoriteController::class, 'index']);

            // Compare
            Route::post('/compare/add', [App\Http\Controllers\Api\Property\CompareController::class, 'store']);
            Route::delete('/compare/remove', [App\Http\Controllers\Api\Property\CompareController::class, 'destroy']);
            Route::get('/compare/list', [App\Http\Controllers\Api\Property\CompareController::class, 'index']);
        });

        // Consultant routes (فقط برای مشاوران)
        Route::middleware(['role:consultant'])->prefix('consultant')->group(function () {
            Route::get('dashboard', [App\Http\Controllers\Api\User\ConsultantController::class, 'dashboard']);
            Route::post('properties', [App\Http\Controllers\Api\Property\PropertyController::class, 'store']);
            Route::put('properties/{property}', [App\Http\Controllers\Api\Property\PropertyController::class, 'update']);
            Route::delete('properties/{property}', [App\Http\Controllers\Api\Property\PropertyController::class, 'destroy']);
        });

        // Admin routes (فقط برای مدیران)
        Route::middleware(['role:admin'])->prefix('admin')->group(function () {
            Route::get('analytics', [App\Http\Controllers\Api\Admin\AnalyticsController::class, 'index']);

            // User management
            Route::prefix('users')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\Admin\UserManagementController::class, 'index']);
                Route::get('/{user}', [App\Http\Controllers\Api\Admin\UserManagementController::class, 'show']);
                Route::put('/{user}', [App\Http\Controllers\Api\Admin\UserManagementController::class, 'update']);
                Route::delete('/{user}', [App\Http\Controllers\Api\Admin\UserManagementController::class, 'destroy']);
            });

            // Property management
            Route::prefix('properties')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\Admin\PropertyManagementController::class, 'index']);
                Route::get('/{property}', [App\Http\Controllers\Api\Admin\PropertyManagementController::class, 'show']);
                Route::put('/{property}/approve', [App\Http\Controllers\Api\Admin\PropertyManagementController::class, 'approve']);
                Route::put('/{property}/reject', [App\Http\Controllers\Api\Admin\PropertyManagementController::class, 'reject']);
                Route::put('/{property}/feature', [App\Http\Controllers\Api\Admin\PropertyManagementController::class, 'feature']);
            });
        });
    });
});
