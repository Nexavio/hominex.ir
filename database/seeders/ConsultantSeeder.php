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

        // یک مشاور تست هم بسازیم
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
    }
}
