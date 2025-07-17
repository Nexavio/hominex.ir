<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class ApiAuthenticate
{
    use ApiResponse;

    public function handle(Request $request, Closure $next): Response
    {
        try {
            // بررسی وجود توکن
            if (!$token = JWTAuth::getToken()) {
                return $this->errorResponse(
                    message: 'توکن احراز هویت ارائه نشده است.',
                    statusCode: 401
                );
            }

            // تأیید توکن و دریافت کاربر
            $user = JWTAuth::authenticate($token);

            if (!$user) {
                return $this->errorResponse(
                    message: 'کاربر یافت نشد.',
                    statusCode: 401
                );
            }

            // بررسی فعال بودن کاربر
            if (!$user->is_active) {
                return $this->errorResponse(
                    message: 'حساب کاربری شما غیرفعال است.',
                    statusCode: 403
                );
            }
        } catch (TokenExpiredException $e) {
            return $this->errorResponse(
                message: 'توکن منقضی شده است.',
                statusCode: 401
            );
        } catch (TokenInvalidException $e) {
            return $this->errorResponse(
                message: 'توکن نامعتبر است.',
                statusCode: 401
            );
        } catch (JWTException $e) {
            return $this->errorResponse(
                message: 'خطا در احراز هویت.',
                statusCode: 401
            );
        } catch (\Exception $e) {
            Log::error('API Authentication error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                message: 'خطا در سیستم احراز هویت.',
                statusCode: 500
            );
        }

        return $next($request);
    }
}
