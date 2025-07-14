<?php
// app/Services/SmsService.php
namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    private $sender;

    public function __construct()
    {
        $this->sender = config('services.kavenegar.sender', '2000660110');
    }

    /**
     * ارسال کد OTP
     */
    public function sendOtpCode(string $phone, string $code): bool
    {
        try {
            // در محیط development، کد رو در لاگ ثبت می‌کنیم
            if (config('app.env') === 'local') {
                Log::info("OTP Code for {$phone}: {$code}");
                return true;
            }

            // در production، با Kavenegar API ارسال می‌شود
            if (!config('services.kavenegar.token')) {
                Log::warning('Kavenegar token not configured');
                return false;
            }

            $api = new \Kavenegar\KavenegarApi(config('services.kavenegar.token'));
            $message = "کد تأیید شما: {$code}\nاین کد تا 10 دقیقه معتبر است.";

            $result = $api->Send($this->sender, $phone, $message);

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
            if (config('app.env') === 'local') {
                Log::info("Welcome message for {$phone}: سلام {$name} عزیز!");
                return true;
            }

            if (!config('services.kavenegar.token')) {
                Log::warning('Kavenegar token not configured');
                return false;
            }

            $api = new \Kavenegar\KavenegarApi(config('services.kavenegar.token'));
            $message = "سلام {$name} عزیز!\nبه پلتفرم املاک ما خوش آمدید. از اعتماد شما متشکریم.";

            $result = $api->Send($this->sender, $phone, $message);

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
