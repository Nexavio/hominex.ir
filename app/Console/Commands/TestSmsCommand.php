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
        $this->info('🔍 test SMS connection starting...');

        // تست اتصال
        $this->info('📡 kavenegar test connection ...');
        $connectionTest = $this->smsService->testConnection();

        if ($connectionTest['success']) {
            $this->info('✅ connection successfully!');
            if (isset($connectionTest['data']['remaining_credit'])) {
                $this->info("💰 credit : {$connectionTest['data']['remaining_credit']}");
            }
        } else {
            $this->error('❌ connection failed ' . $connectionTest['message']);
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
            $this->info('✅ send sms successfully!');
            $this->info("code : {$testCode}");
        } else {
            $this->error('❌ error when send sms!');
            $this->info('لطفاً لاگ‌های Laravel را بررسی کنید: tail -f storage/logs/laravel.log');
        }

        return 0;
    }
}
