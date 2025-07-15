<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Consultant;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;

class ConsultantSeeder extends Seeder
{
    public function run(): void
    {
        // ابتدا مشاور پیش‌فرض سایت (ID=1) را ایجاد می‌کنیم
        $this->createDefaultConsultant();

        // سپس مشاورین واقعی را ایجاد می‌کنیم
        $this->createRealConsultants();

        // یک مشاور تست هم می‌سازیم
        $this->createTestConsultant();
    }

    /**
     * ایجاد مشاور پیش‌فرض سایت (ID=1)
     */
    private function createDefaultConsultant(): void
    {
        $defaultUser = User::factory()->consultant()->create([
            'phone' => '02188776655',
            'full_name' => 'تیم مشاوره هومینکس',
            'email' => 'info@hominex.ir',
            'phone_verified_at' => now(),
        ]);

        Consultant::create([
            'user_id' => $defaultUser->id,
            'company_name' => 'هومینکس - سامانه جامع املاک',
            'bio' => 'تیم متخصص مشاوره املاک سایت هومینکس آماده ارائه بهترین خدمات مشاوره‌ای در زمینه خرید، فروش و اجاره انواع املاک می‌باشد.',
            'contact_phone' => '021-88776655',
            'contact_whatsapp' => '09120000000',
            'contact_telegram' => '@hominex_support',
            'contact_instagram' => 'hominex.ir',
            'is_verified' => true,
        ]);

        $this->command->info('✅ مشاور پیش‌فرض سایت (ID=1) ایجاد شد');
    }

    /**
     * ایجاد مشاورین واقعی
     */
    private function createRealConsultants(): void
    {
        // ایجاد کاربران مشاور
        $consultantUsers = User::factory(15)->consultant()->create();

        $companies = [
            'گروه املاک پردیس',
            'املاک بزرگ تهران',
            'شرکت ساختمانی آسمان',
            'املاک و مستغلات شهر',
            'گروه سرمایه‌گذاری پایتخت',
            'املاک دریا',
            'شرکت توسعه شهری',
            'املاک طلایی',
            'گروه املاک پیشرو',
            'املاک و ساختمان رویا',
        ];

        $bios = [
            'با بیش از 10 سال تجربه در زمینه خرید و فروش املاک در تهران و حومه، آماده ارائه مشاوره تخصصی هستم.',
            'متخصص در معاملات آپارتمان‌های لوکس و ویلاهای شمال تهران با تیم حرفه‌ای و قابل اعتماد.',
            'مشاور املاک با رکورد موفق در انجام بیش از 500 معامله موفق در سال‌های اخیر.',
            'کارشناس ارشد مهندسی عمران و مشاور املاک با تخصص در ارزیابی و قیمت‌گذاری ملک.',
            'مشاور املاک منطقه غرب تهران با شناخت کامل از بازار و قیمت‌های روز.',
            'متخصص فروش زمین و باغ با تجربه گسترده در مناطق ییلاقی و کوهستانی.',
            'مشاور املاک تجاری و اداری با تمرکز بر مغازه و دفاتر کاری در مناطق تجاری تهران.',
            'کارشناس املاک با تخصص در سرمایه‌گذاری و خرید ملک برای اجاره.',
            'مشاور املاک با تیم پشتیبانی 24 ساعته و خدمات پس از فروش.',
            'متخصص املاک لوکس و پنت‌هاوس با پورتفولیو غنی از ملک‌های درجه یک.',
        ];

        foreach ($consultantUsers as $index => $user) {
            Consultant::create([
                'user_id' => $user->id,
                'company_name' => $companies[$index % count($companies)],
                'bio' => $bios[$index % count($bios)],
                'contact_phone' => $user->phone,
                'contact_whatsapp' => $user->phone,
                'contact_telegram' => '@consultant' . $user->id,
                'contact_instagram' => 'consultant' . $user->id,
                'is_verified' => fake()->boolean(80), // 80% احتمال تأیید شده باشند
            ]);
        }

        $this->command->info('✅ 15 مشاور واقعی ایجاد شد');
    }

    /**
     * ایجاد مشاور تست
     */
    private function createTestConsultant(): void
    {
        $testConsultantUser = User::factory()->consultant()->create([
            'phone' => '09333333333',
            'full_name' => 'احمد محمدی',
            'email' => 'test.consultant@example.com',
            'phone_verified_at' => now(),
        ]);

        Consultant::create([
            'user_id' => $testConsultantUser->id,
            'company_name' => 'املاک تست',
            'bio' => 'مشاور املاک تست با بیش از 5 سال تجربه در زمینه خرید و فروش انواع ملک.',
            'contact_phone' => $testConsultantUser->phone,
            'contact_whatsapp' => $testConsultantUser->phone,
            'contact_telegram' => '@testconsultant',
            'contact_instagram' => 'testconsultant',
            'is_verified' => true,
        ]);

        $this->command->info('✅ مشاور تست ایجاد شد');
    }
}
