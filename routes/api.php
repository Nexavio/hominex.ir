<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\OtpController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\User\ConsultantUpgradeController;
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\ConsultantController;
use App\Http\Controllers\Api\Property\PropertyController;
use App\Http\Controllers\Api\Property\FavoriteController;
use App\Http\Controllers\Api\Property\CompareController;
use App\Http\Controllers\Api\Admin\AnalyticsController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Admin\PropertyManagementController;
use App\Http\Controllers\Api\Admin\ConsultantController as AdminConsultantController;
use App\Http\Controllers\Api\Admin\NotificationController as AdminNotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// =====================================================
// 🔑 Authentication Routes (Public)
// =====================================================
Route::prefix('auth')->group(function () {

    // Public authentication routes
    Route::post('register', [RegisterController::class, 'register']);
    Route::post('verify-registration', [RegisterController::class, 'verifyOtp']);
    Route::post('login', [LoginController::class, 'login']);
    Route::post('verify-login', [LoginController::class, 'verifyLoginOtp']);

    // OTP routes with rate limiting
    Route::middleware(['throttle:otp'])->group(function () {
        Route::post('send-otp', [OtpController::class, 'send']);
    });

    // Protected authentication routes
    Route::middleware(['auth:api'])->group(function () {
        Route::post('logout', [LoginController::class, 'logout']);
        Route::post('refresh', [LoginController::class, 'refresh']);
        Route::get('me', [LoginController::class, 'me']);
    });
});

// =====================================================
// 📊 Public API Routes
// =====================================================
Route::prefix('v1')->group(function () {

    // Health check
    Route::get('health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0'
        ]);
    });

    // Public property routes (no auth required)
    Route::prefix('properties')->group(function () {
        Route::get('/', [PropertyController::class, 'index']);
        Route::get('/{property}', [PropertyController::class, 'show']);
    });
});

// =====================================================
// 🔐 Protected API Routes (Authentication Required)
// =====================================================
Route::prefix('v1')->middleware(['auth:api'])->group(function () {

    // =====================================================
    // 👤 User Routes
    // =====================================================
    Route::prefix('user')->group(function () {

        // Profile management
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);

        // Consultant upgrade requests
        Route::prefix('consultant-upgrade')->group(function () {
            Route::get('status', [ConsultantUpgradeController::class, 'getRequestStatus']);
            Route::post('submit', [ConsultantUpgradeController::class, 'submitRequest']);
        });

        // User favorites
        Route::prefix('favorites')->group(function () {
            Route::get('/', [FavoriteController::class, 'index']);
            Route::get('stats', [FavoriteController::class, 'stats']);
            Route::post('/{property}', [FavoriteController::class, 'store']);
            Route::delete('/{property}', [FavoriteController::class, 'destroy']);
            Route::get('/{property}/status', [FavoriteController::class, 'status']);
        });

        // Property comparison
        Route::prefix('compare')->group(function () {
            Route::get('/', [CompareController::class, 'index']);
            Route::post('/add', [CompareController::class, 'store']);
            Route::delete('/remove', [CompareController::class, 'destroy']);
        });
    });

    // =====================================================
    // 🏢 Consultant Routes (Role: consultant)
    // =====================================================
    Route::middleware(['role:consultant'])->prefix('consultant')->group(function () {

        Route::get('dashboard', [ConsultantController::class, 'dashboard']);

        // Consultant's property management
        Route::prefix('properties')->group(function () {
            Route::get('/', [ConsultantController::class, 'properties']);
            Route::post('/', [PropertyController::class, 'store']);
            Route::get('/{property}', [PropertyController::class, 'show']);
            Route::put('/{property}', [PropertyController::class, 'update']);
            Route::delete('/{property}', [PropertyController::class, 'destroy']);
        });

        // Consultation requests management
        Route::prefix('consultations')->group(function () {
            Route::get('/', [ConsultantController::class, 'consultationRequests']);
            Route::put('/{consultation}', [ConsultantController::class, 'updateConsultation']);
        });
    });

    // =====================================================
    // 👑 Admin Routes (Role: admin)
    // =====================================================
    Route::middleware(['role:admin'])->prefix('admin')->group(function () {

        // =====================================================
        // 📊 Analytics
        // =====================================================
        Route::prefix('analytics')->group(function () {
            Route::get('/', [AnalyticsController::class, 'index']);
            Route::get('users', [AnalyticsController::class, 'userStats']);
            Route::get('properties', [AnalyticsController::class, 'propertyStats']);
            Route::get('consultants', [AnalyticsController::class, 'consultantStats']);
            Route::post('time-range', [AnalyticsController::class, 'timeRange']);
        });

        // =====================================================
        // 👥 User Management
        // =====================================================
        Route::prefix('users')->group(function () {
            Route::get('/', [UserManagementController::class, 'index']);
            Route::get('stats', [UserManagementController::class, 'stats']);
            Route::get('{user}', [UserManagementController::class, 'show']);
            Route::put('{user}', [UserManagementController::class, 'update']);
            Route::post('{user}/toggle-active', [UserManagementController::class, 'toggleActive']);
            Route::post('{user}/verify-phone', [UserManagementController::class, 'verifyPhone']);
            Route::post('{user}/change-role', [UserManagementController::class, 'changeRole']);
        });

        // =====================================================
        // 🏢 Consultant Management
        // =====================================================
        Route::prefix('consultant-requests')->group(function () {
            Route::get('/', [AdminConsultantController::class, 'pendingRequests']);
            Route::get('{consultant}', [AdminConsultantController::class, 'show']);
            Route::post('{consultant}/approve', [AdminConsultantController::class, 'approve']);
            Route::post('{consultant}/reject', [AdminConsultantController::class, 'reject']);
        });

        // =====================================================
        // 🏠 Property Management
        // =====================================================
        Route::prefix('properties')->group(function () {
            Route::get('/', [PropertyManagementController::class, 'index']);
            Route::get('{property}', [PropertyManagementController::class, 'show']);
            Route::post('{property}/approve', [PropertyManagementController::class, 'approve']);
            Route::post('{property}/reject', [PropertyManagementController::class, 'reject']);
            Route::post('{property}/feature', [PropertyManagementController::class, 'feature']);
            Route::delete('{property}', [PropertyManagementController::class, 'destroy']);
        });

        // =====================================================
        // 🔔 Notification Management
        // =====================================================
        Route::prefix('notifications')->group(function () {
            Route::get('/', [AdminNotificationController::class, 'index']);
            Route::get('stats', [AdminNotificationController::class, 'stats']);
            Route::post('broadcast', [AdminNotificationController::class, 'sendBroadcast']);
        });
    });
});
