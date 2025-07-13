<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Traits\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class CheckOtpLimit
{
    use ApiResponse;

    private const RATE_LIMIT_KEY = 'otp_rate_limit:';
    private const RATE_LIMIT_PER_HOUR = 3;

    public function handle(Request $request, Closure $next): Response
    {
        $phone = $request->input('phone');

        if (!$phone) {
            return $next($request);
        }

        $key = self::RATE_LIMIT_KEY . $phone;
        $attempts = Cache::get($key, 0);

        if ($attempts >= self::RATE_LIMIT_PER_HOUR) {
            return $this->rateLimitResponse(
                message: 'شما بیش از حد مجاز درخواست کد تأیید داده‌اید. لطفا یک ساعت دیگر تلاش کنید.',
                retryAfter: 3600
            );
        }

        return $next($request);
    }
}
