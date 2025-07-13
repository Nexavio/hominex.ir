<?php

namespace App\Services;

use Kavenegar\KavenegarApi;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private KavenegarApi $api;
    private string $sender;

    public function __construct()
    {
        $this->api = new KavenegarApi(config('services.kavenegar.token'));
        $this->sender = config('services.kavenegar.sender', '2000660110');
    }

    /**
     * ارسال کد OTP
     */
    public function sendOtpCode(string $phone, string $code): bool
    {
        try {
            $message = "کد تأیید شما: {$code}\nاین کد تا 10 دقیقه معتبر است.";

            $result = $this->api->Send($this->sender, $phone, $message);

            Log::info('OTP sent successfully', [
                'phone' => $phone,
                'result' => $result
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send OTP', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * ارسال پیام خوش‌آمدگویی
     */
    public function sendWelcomeMessage(string $phone, string $name): bool
    {
        try {
            $message = "سلام {$name} عزیز!\nبه پلتفرم املاک ما خوش آمدید. از اعتماد شما متشکریم.";

            $result = $this->api->Send($this->sender, $phone, $message);

            Log::info('Welcome message sent', [
                'phone' => $phone,
                'result' => $result
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send welcome message', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
