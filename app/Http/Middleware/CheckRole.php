<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    use ApiResponse;

    public function handle(Request $request, Closure $next, string $role): Response
    {
        // بررسی اینکه کاربر لاگین کرده باشد
        if (!auth()->check()) {
            return $this->unauthorizedResponse('شما باید وارد شوید.');
        }

        $user = auth()->user();

        // بررسی اینکه کاربر وجود دارد
        if (!$user) {
            return $this->unauthorizedResponse('کاربر یافت نشد.');
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

        // بررسی فعال بودن حساب کاربری
        if (!$user->is_active) {
            return $this->errorResponse(
                message: 'حساب کاربری شما غیرفعال است.',
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
    }
}
