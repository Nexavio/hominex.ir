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
        $user = auth()->user();

        if (!$user) {
            return $this->unauthorizedResponse('برای دسترسی به این بخش باید وارد شوید.');
        }

        $requiredRole = UserRole::from($role);

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
