<?php
// app/Console/Commands/TestSmsCommand.php
namespace App\Console\Commands;

use App\Services\SmsService;
use Illuminate\Console\Command;

class TestSmsCommand extends Command
{
    protected $signature = 'sms:test {phone?} {--connection-only}';
    protected $description = 'تست ارسال SMS و اتصال به Kavenegar';

    public function __construct(private SmsService $smsService)
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('🔍 شروع تست SMS...');

        // تست اتصال
        $this->info('📡 تست اتصال به Kavenegar...');
        $connectionTest = $this->smsService->testConnection();

        if ($connectionTest['success']) {
            $this->info('✅ اتصال موفق!');
            if (isset($connectionTest['data']['remaining_credit'])) {
                $this->info("💰 اعتبار باقی‌مانده: {$connectionTest['data']['remaining_credit']}");
            }
        } else {
            $this->error('❌ خطا در اتصال: ' . $connectionTest['message']);
            return 1;
        }

        // اگر فقط تست اتصال خواسته شده
        if ($this->option('connection-only')) {
            return 0;
        }

        // دریافت شماره تلفن
        $phone = $this->argument('phone') ?? $this->ask('شماره تلفن برای تست (مثال: 09123456789)');

        if (empty($phone)) {
            $this->error('شماره تلفن الزامی است');
            return 1;
        }

        // ایجاد کد تست
        $testCode = rand(100000, 999999);

        $this->info("📱 ارسال کد تست {$testCode} به {$phone}...");

        // ارسال SMS
        $result = $this->smsService->sendOtpCode($phone, (string)$testCode);

        if ($result) {
            $this->info('✅ SMS با موفقیت ارسال شد!');
            $this->info("کد ارسال شده: {$testCode}");
        } else {
            $this->error('❌ خطا در ارسال SMS');
            $this->info('لطفاً لاگ‌های Laravel را بررسی کنید: tail -f storage/logs/laravel.log');
        }

        return 0;
    }
}
