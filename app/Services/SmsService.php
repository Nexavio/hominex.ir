<?php
// app/Services/SmsService.php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kavenegar\KavenegarApi;

class SmsService
{
    private $sender;
    private $token;

    public function __construct()
    {
        $this->token = config('services.kavenegar.token');
        $this->sender = config('services.kavenegar.sender', '2000660110');

        Log::info('SmsService initialized', [
            'token_length' => strlen($this->token ?? ''),
            'sender' => $this->sender,
            'env' => config('app.env')
        ]);
    }

    /**
     * ارسال کد OTP
     */
    public function sendOtpCode(string $phone, string $code): bool
    {
        try {
            // در محیط development، کد رو در لاگ ثبت می‌کنیم
            if (config('app.env') === 'local') {
                Log::info("📱 OTP Code for {$phone}: {$code}");

                // اگر می‌خواهید در محیط local هم SMS ارسال شود، این خط را کامنت کنید
                // return true;
            }

            // بررسی token
            if (empty($this->token)) {
                Log::error('Kavenegar token is empty or not configured');
                return false;
            }

            Log::info('Attempting to send SMS', [
                'phone' => $phone,
                'code_length' => strlen($code),
                'sender' => $this->sender
            ]);

            // ایجاد instance از Kavenegar API
            $api = new KavenegarApi($this->token);

            // تنظیم پیام
            $message = "کد تأیید شما: {$code}\nاین کد تا 10 دقیقه معتبر است.\nhominex.ir";

            // ارسال پیامک
            $result = $api->Send($this->sender, $phone, $message);

            Log::info('SMS sent successfully', [
                'phone' => $phone,
                'result_status' => $result[0]->status ?? 'unknown',
                'result_messageid' => $result[0]->messageid ?? 'unknown'
            ]);

            return true;

        } catch (\Kavenegar\Exceptions\ApiException $e) {
            Log::error('Kavenegar API Exception', [
                'phone' => $phone,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);
            return false;

        } catch (\Kavenegar\Exceptions\HttpException $e) {
            Log::error('Kavenegar HTTP Exception', [
                'phone' => $phone,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage()
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('General SMS sending failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
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
                Log::info("📱 Welcome message for {$phone}: سلام {$name} عزیز!");
                return true;
            }

            if (empty($this->token)) {
                Log::warning('Kavenegar token not configured for welcome message');
                return false;
            }

            $api = new KavenegarApi($this->token);
            $message = "سلام {$name} عزیز!\nبه پلتفرم املاک هومینکس خوش آمدید. از اعتماد شما متشکریم.\nhominex.ir";

            $result = $api->Send($this->sender, $phone, $message);

            Log::info('Welcome message sent', [
                'phone' => $phone,
                'result_status' => $result[0]->status ?? 'unknown'
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

    /**
     * تست اتصال به Kavenegar
     */
    public function testConnection(): array
    {
        try {
            if (empty($this->token)) {
                return [
                    'success' => false,
                    'message' => 'Token خالی است'
                ];
            }

            $api = new KavenegarApi($this->token);

            // استفاده از متد AccountInfo برای تست
            $result = $api->AccountInfo();

            return [
                'success' => true,
                'message' => 'اتصال موفق',
                'data' => [
                    'remaining_credit' => $result->remaincredit ?? 'نامشخص',
                    'expiry_date' => $result->expiredate ?? 'نامشخص'
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'خطا در اتصال: ' . $e->getMessage()
            ];
        }
    }
}
