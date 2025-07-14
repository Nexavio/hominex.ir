<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Notification;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $adminUsers = User::where('user_type', UserRole::ADMIN)->get();

        if ($users->isEmpty() || $adminUsers->isEmpty()) {
            $this->command->info('ابتدا باید کاربران و ادمین‌ها وجود داشته باشند.');
            return;
        }

        $admin = $adminUsers->first();

        // اعلان‌های عمومی برای همه کاربران
        $publicNotifications = [
            [
                'title' => 'خوش آمدید به هومینکس',
                'message' => 'خوش آمدید! از امکانات کامل سایت لذت ببرید و ملک مورد نظرتان را پیدا کنید.',
                'notification_type' => 'success',
                'target_type' => 'all_users',
                'priority' => 'normal',
                'action_url' => '/properties',
                'action_text' => 'مشاهده املاک',
            ],
            [
                'title' => 'به‌روزرسانی سیستم',
                'message' => 'سیستم با امکانات جدید به‌روزرسانی شد. اکنون می‌توانید از امکان مقایسه املاک استفاده کنید.',
                'notification_type' => 'info',
                'target_type' => 'all_users',
                'priority' => 'normal',
                'action_url' => '/compare',
                'action_text' => 'مقایسه املاک',
            ],
            [
                'title' => 'تخفیف ویژه برای مشاورین',
                'message' => 'تا پایان ماه، هزینه ویژه کردن املاک 30% تخفیف دارد.',
                'notification_type' => 'success',
                'target_type' => 'consultants',
                'priority' => 'high',
                'action_url' => '/consultant/properties',
                'action_text' => 'مدیریت املاک',
                'expires_at' => now()->addDays(30),
            ],
        ];

        foreach ($publicNotifications as $notificationData) {
            if ($notificationData['target_type'] === 'all_users') {
                // برای همه کاربران
                foreach ($users as $user) {
                    Notification::create(array_merge($notificationData, [
                        'user_id' => $user->id,
                        'sender_id' => $admin->id,
                        'is_read' => fake()->boolean(30), // 30% خوانده شده
                        'read_at' => fake()->boolean(30) ? fake()->dateTimeBetween('-1 week', 'now') : null,
                        'created_at' => fake()->dateTimeBetween('-2 weeks', 'now'),
                    ]));
                }
            } elseif ($notificationData['target_type'] === 'consultants') {
                // فقط برای مشاورین
                $consultants = User::where('user_type', UserRole::CONSULTANT)->get();
                foreach ($consultants as $consultant) {
                    Notification::create(array_merge($notificationData, [
                        'user_id' => $consultant->id,
                        'sender_id' => $admin->id,
                        'is_read' => fake()->boolean(20),
                        'read_at' => fake()->boolean(20) ? fake()->dateTimeBetween('-1 week', 'now') : null,
                        'created_at' => fake()->dateTimeBetween('-1 week', 'now'),
                    ]));
                }
            }
        }

        // اعلان‌های شخصی تصادفی
        $personalNotificationTemplates = [
            [
                'title' => 'ملک شما تأیید شد',
                'message' => 'ملک شما با موفقیت تأیید و منتشر شد.',
                'notification_type' => 'success',
                'target_type' => 'specific_user',
                'priority' => 'normal',
                'action_url' => '/consultant/properties',
                'action_text' => 'مشاهده ملک',
            ],
            [
                'title' => 'درخواست مشاوره جدید',
                'message' => 'یک درخواست مشاوره جدید برای شما ثبت شده است.',
                'notification_type' => 'info',
                'target_type' => 'specific_user',
                'priority' => 'high',
                'action_url' => '/consultant/consultations',
                'action_text' => 'مشاهده درخواست',
            ],
            [
                'title' => 'پیام جدید دریافت شد',
                'message' => 'یک پیام جدید در صندوق پیام‌های شما دریافت شده است.',
                'notification_type' => 'info',
                'target_type' => 'specific_user',
                'priority' => 'normal',
                'action_url' => '/messages',
                'action_text' => 'مشاهده پیام‌ها',
            ],
            [
                'title' => 'تغییر قیمت ملک مورد علاقه',
                'message' => 'قیمت یکی از املاک مورد علاقه شما تغییر کرده است.',
                'notification_type' => 'warning',
                'target_type' => 'specific_user',
                'priority' => 'normal',
                'action_url' => '/favorites',
                'action_text' => 'مشاهده علاقه‌مندی‌ها',
            ],
            [
                'title' => 'حساب کاربری شما فعال شد',
                'message' => 'حساب کاربری شما با موفقیت فعال شد. از تمام امکانات استفاده کنید.',
                'notification_type' => 'success',
                'target_type' => 'specific_user',
                'priority' => 'normal',
                'action_url' => '/profile',
                'action_text' => 'مشاهده پروفایل',
            ],
        ];

        // ایجاد 100 اعلان شخصی تصادفی
        for ($i = 0; $i < 100; $i++) {
            $user = $users->random();
            $template = fake()->randomElement($personalNotificationTemplates);

            Notification::create(array_merge($template, [
                'user_id' => $user->id,
                'sender_id' => $admin->id,
                'is_read' => fake()->boolean(40), // 40% خوانده شده
                'read_at' => fake()->boolean(40) ? fake()->dateTimeBetween('-1 month', 'now') : null,
                'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
            ]));
        }

        $this->command->info('اعلان‌های تست با موفقیت ایجاد شدند.');
    }
}
