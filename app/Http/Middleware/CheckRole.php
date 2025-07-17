<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    use ApiResponse;

    public function handle(Request $request, Closure $next, string $role): Response
    {
        try {
            // فرض می‌کنیم کاربر قبلاً توسط api.auth middleware احراز هویت شده
            $user = auth('api')->user();

            if (!$user) {
                return $this->errorResponse(
                    message: 'کاربر احراز هویت نشده است.',
                    statusCode: 401
                );
            }

            // تبدیل role string به UserRole enum
            try {
                $requiredRole = UserRole::from($role);
            } catch (\ValueError $e) {
                return $this->errorResponse(
                    message: 'نقش کاربری نامعتبر است.',
                    statusCode: 400
                );
            }

            // بررسی نقش کاربر
            if ($user->user_type !== $requiredRole) {
                return $this->errorResponse(
                    message: 'شما مجاز به دسترسی به این بخش نیستید.',
                    statusCode: 403
                );
            }

            // بررسی تأیید شماره تماس
            if (!$user->phone_verified_at) {
                return $this->errorResponse(
                    message: 'شماره تماس شما هنوز تأیید نشده است.',
                    statusCode: 403
                );
            }

            return $next($request);
        } catch (\Exception $e) {
            Log::error('CheckRole middleware error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => auth('api')->id(),
                'required_role' => $role
            ]);

            return $this->errorResponse(
                message: 'خطا در بررسی دسترسی کاربر.',
                statusCode: 500
            );
        }
    }
}
