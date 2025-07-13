<?php

namespace App\Services;

use App\Models\OtpCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class OtpService
{
    private const OTP_LENGTH = 6;
    private const OTP_EXPIRY_MINUTES = 10;
    private const MAX_ATTEMPTS = 3;
    private const RATE_LIMIT_KEY = 'otp_rate_limit:';
    private const RATE_LIMIT_PER_HOUR = 3;

    public function __construct(
        private SmsService $smsService
    ) {}

    /**
     * تولید و ارسال کد OTP
     */
    public function generateAndSend(string $phone, string $purpose = 'login'): array
    {
        // بررسی محدودیت تعداد درخواست در ساعت
        if (!$this->checkRateLimit($phone)) {
            return [
                'success' => false,
                'message' => 'شما بیش از حد مجاز درخواست کد تأیید داده‌اید. لطفا یک ساعت دیگر تلاش کنید.',
                'data' => [
                    'retry_after' => $this->getRemainingCooldown($phone)
                ]
            ];
        }

        // غیرفعال کردن کدهای قبلی
        $this->deactivatePreviousCodes($phone, $purpose);

        // تولید کد جدید
        $code = $this->generateCode();

        // ذخیره در دیتابیس
        $otpCode = OtpCode::create([
            'phone' => $phone,
            'code' => $code,
            'purpose' => $purpose,
            'expires_at' => Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'attempts' => 0
        ]);

        // ارسال پیامک
        $smsSent = $this->smsService->sendOtpCode($phone, $code);

        if ($smsSent) {
            // افزایش شمارنده rate limit
            $this->incrementRateLimit($phone);

            return [
                'success' => true,
                'message' => 'کد تأیید با موفقیت ارسال شد.',
                'data' => [
                    'expires_at' => $otpCode->expires_at->toISOString(),
                    'expires_in_seconds' => self::OTP_EXPIRY_MINUTES * 60
                ]
            ];
        }

        // حذف کد در صورت عدم ارسال
        $otpCode->delete();

        return [
            'success' => false,
            'message' => 'خطا در ارسال کد تأیید. لطفا دوباره تلاش کنید.',
            'data' => null
        ];
    }

    /**
     * تأیید کد OTP
     */
    public function verify(string $phone, string $code, string $purpose = 'login'): array
    {
        $otpCode = OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otpCode) {
            return [
                'success' => false,
                'message' => 'کد تأیید نامعتبر یا منقضی شده است.',
                'data' => null
            ];
        }

        // بررسی تعداد تلاش‌های اشتباه
        if ($otpCode->attempts >= self::MAX_ATTEMPTS) {
            $otpCode->delete();
            return [
                'success' => false,
                'message' => 'کد تأیید به دلیل تلاش‌های متعدد اشتباه باطل شده است.',
                'data' => null
            ];
        }

        // بررسی صحت کد
        if ($otpCode->code !== $code) {
            $otpCode->increment('attempts');

            $remainingAttempts = self::MAX_ATTEMPTS - $otpCode->attempts;

            return [
                'success' => false,
                'message' => "کد تأیید اشتباه است. {$remainingAttempts} تلاش باقی مانده.",
                'data' => [
                    'remaining_attempts' => $remainingAttempts
                ]
            ];
        }

        // تأیید کد
        $otpCode->update([
            'verified_at' => Carbon::now()
        ]);

        return [
            'success' => true,
            'message' => 'کد تأیید با موفقیت تأیید شد.',
            'data' => [
                'verified_at' => $otpCode->verified_at->toISOString()
            ]
        ];
    }

    /**
     * تولید کد 6 رقمی
     */
    private function generateCode(): string
    {
        return str_pad(random_int(100000, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * غیرفعال کردن کدهای قبلی
     */
    private function deactivatePreviousCodes(string $phone, string $purpose): void
    {
        OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();
    }

    /**
     * بررسی محدودیت تعداد درخواست
     */
    private function checkRateLimit(string $phone): bool
    {
        $key = self::RATE_LIMIT_KEY . $phone;
        $attempts = Cache::get($key, 0);

        return $attempts < self::RATE_LIMIT_PER_HOUR;
    }

    /**
     * افزایش شمارنده rate limit
     */
    private function incrementRateLimit(string $phone): void
    {
        $key = self::RATE_LIMIT_KEY . $phone;
        $ttl = 3600; // 1 hour

        if (Cache::has($key)) {
            Cache::increment($key);
        } else {
            Cache::put($key, 1, $ttl);
        }
    }

    /**
     * دریافت زمان باقی‌مانده تا پایان محدودیت
     */
    private function getRemainingCooldown(string $phone): int
    {
        $key = self::RATE_LIMIT_KEY . $phone;

        if (Cache::has($key)) {
            // محاسبه زمان باقی‌مانده (تقریبی)
            return 3600; // برمی‌گرداند 1 ساعت به ثانیه
        }

        return 0;
    }
}
