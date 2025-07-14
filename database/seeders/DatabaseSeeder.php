<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');

        // مرحله 1: ایجاد کاربران اولیه (پیش‌نیاز برای سایر seeders)
        $this->command->info('👥 Creating initial users...');
        $this->createInitialUsers();

        // مرحله 2: ایجاد انواع املاک و امکانات (پیش‌نیاز برای املاک)
        $this->command->info('🏠 Setting up property types and amenities...');
        $this->call([
            PropertyTypeSeeder::class,
            PropertyAmenitySeeder::class,
        ]);

        // مرحله 3: ایجاد مشاوران (پیش‌نیاز برای املاک)
        $this->command->info('👨‍💼 Creating consultants...');
        $this->call(ConsultantSeeder::class);

        // مرحله 4: ایجاد املاک (پیش‌نیاز برای تصاویر و سایر روابط)
        $this->command->info('🏢 Creating properties...');
        $this->call(PropertySeeder::class);

        // مرحله 5: ایجاد تصاویر املاک
        $this->command->info('📸 Adding property images...');
        $this->call(PropertyImageSeeder::class);

        // مرحله 6: ایجاد روابط و تعاملات کاربران
        $this->command->info('❤️ Creating user interactions...');
        $this->call([
            FavoriteSeeder::class,
            ConsultationRequestSeeder::class,
        ]);

        // مرحله 7: ایجاد پیام‌ها (پس از ایجاد درخواست‌های مشاوره)
        $this->command->info('💬 Creating messages...');
        $this->call(MessageSeeder::class);

        // مرحله 8: ایجاد اعلانات
        $this->command->info('🔔 Creating notifications...');
        $this->call(NotificationSeeder::class);

        // مرحله 9: تنظیمات سایت (در انتها)
        $this->command->info('⚙️ Setting up site settings...');
        $this->call(SiteSettingSeeder::class);

        $this->command->info('✅ Database seeding completed successfully!');
        $this->displaySeedingSummary();
    }

    /**
     * ایجاد کاربران اولیه ضروری
     */
    private function createInitialUsers(): void
    {
        // ایجاد کاربران تصادفی (Factory موجود را حفظ می‌کنیم)
        User::factory(10)->create();

        // کاربر تست عادی
        $testUser = User::factory()->create([
            'phone' => '09123456789',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'user_type' => UserRole::REGULAR,
            'phone_verified_at' => now(),
        ]);

        $this->command->info("   📱 Test User created: {$testUser->phone}");

        // کاربر ادمین
        $adminUser = User::factory()->admin()->create([
            'phone' => '09111111111',
            'full_name' => 'Admin User',
            'email' => 'admin@example.com',
            'phone_verified_at' => now(),
        ]);

        $this->command->info("   👑 Admin User created: {$adminUser->phone}");

        // کاربر مشاور تست
        $consultantUser = User::factory()->consultant()->create([
            'phone' => '09222222222',
            'full_name' => 'Consultant User',
            'email' => 'consultant@example.com',
            'phone_verified_at' => now(),
        ]);

        $this->command->info("   🏢 Consultant User created: {$consultantUser->phone}");

        // کاربران عادی اضافی برای تست
        User::factory(20)->create([
            'user_type' => UserRole::REGULAR,
            'phone_verified_at' => now(),
        ]);

        $this->command->info("   👤 20 additional regular users created");
    }

    /**
     * نمایش خلاصه اطلاعات seed شده
     */
    private function displaySeedingSummary(): void
    {
        $this->command->newLine();
        $this->command->info('📊 Seeding Summary:');
        $this->command->table(
            ['Model', 'Count'],
            [
                ['Users', User::count()],
                ['Consultants', \App\Models\Consultant::count()],
                ['Property Types', \App\Models\PropertyType::count()],
                ['Property Amenities', \App\Models\PropertyAmenity::count()],
                ['Properties', \App\Models\Property::count()],
                ['Property Images', \App\Models\PropertyImage::count()],
                ['Favorites', \App\Models\Favorite::count()],
                ['Consultation Requests', \App\Models\ConsultationRequest::count()],
                ['Messages', \App\Models\Message::count()],
                ['Notifications', \App\Models\Notification::count()],
                ['Site Settings', \App\Models\SiteSetting::count()],
            ]
        );

        $this->command->newLine();
        $this->command->info('🔑 Test Accounts:');
        $this->command->table(
            ['Role', 'Phone', 'Email', 'Password'],
            [
                ['Regular User', '09123456789', 'test@example.com', 'password'],
                ['Admin', '09111111111', 'admin@example.com', 'password'],
                ['Consultant', '09222222222', 'consultant@example.com', 'password'],
            ]
        );

        $this->command->newLine();
        $this->command->info('🚀 You can now test the application with the seeded data!');

        // لاگ کردن تکمیل seeding
        Log::info('Database seeding completed successfully', [
            'users_count' => User::count(),
            'properties_count' => \App\Models\Property::count(),
            'seeded_at' => now()->toISOString()
        ]);
    }
}
