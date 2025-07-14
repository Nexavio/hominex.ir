<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Consultant;
use App\Models\Property;
use App\Models\ConsultationRequest;
use App\Models\Message;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        $regularUsers = User::where('user_type', UserRole::REGULAR)->get();
        $consultantUsers = User::where('user_type', UserRole::CONSULTANT)->get();
        $consultationRequests = ConsultationRequest::with(['user', 'consultant.user'])->get();
        $properties = Property::where('status', 'approved')->get();

        if ($regularUsers->isEmpty() || $consultantUsers->isEmpty()) {
            $this->command->info('ابتدا باید کاربران عادی و مشاوران وجود داشته باشند.');
            return;
        }

        $userMessages = [
            'سلام، وقت بخیر',
            'آیا این ملک هنوز موجود است؟',
            'ممکن است قیمت دقیق‌تر اعلام کنید؟',
            'چه زمانی می‌توانم بازدید کنم؟',
            'آیا امکان تخفیف وجود دارد؟',
            'مدارک ملک آماده است؟',
            'محله چطور است؟ امنیت خوبی دارد؟',
            'آیا پارکینگ دارد؟',
            'دسترسی به مترو چطور است؟',
            'متشکرم از پاسخ‌تان',
        ];

        $consultantMessages = [
            'سلام و وقت بخیر',
            'بله، ملک موجود است',
            'قیمت کاملاً قابل مذاکره است',
            'امروز تا ساعت 6 عصر امکان بازدید دارید',
            'این ملک بسیار مناسب برای سرمایه‌گذاری است',
            'تمام مدارک آماده و قابل انتقال است',
            'محله بسیار آرام و امن است',
            'پارکینگ اختصاصی دارد',
            '5 دقیقه تا ایستگاه مترو',
            'خواهش می‌کنم، در خدمت شما هستیم',
            'آیا سوال دیگری دارید؟',
            'می‌توانم املاک مشابه دیگری نیز معرفی کنم',
        ];

        // پیام‌هایی بر اساس درخواست‌های مشاوره
        foreach ($consultationRequests->take(50) as $request) {
            if ($request->user && $request->consultant && $request->consultant->user) {
                $messageCount = fake()->numberBetween(2, 8);

                for ($i = 0; $i < $messageCount; $i++) {
                    $isFromUser = $i % 2 === 0; // پیام‌های متناوب

                    if ($isFromUser) {
                        $sender = $request->user;
                        $receiver = $request->consultant->user;
                        $messageText = fake()->randomElement($userMessages);
                    } else {
                        $sender = $request->consultant->user;
                        $receiver = $request->user;
                        $messageText = fake()->randomElement($consultantMessages);
                    }

                    Message::create([
                        'sender_id' => $sender->id,
                        'receiver_id' => $receiver->id,
                        'property_id' => $request->property_id,
                        'consultation_request_id' => $request->id,
                        'message' => $messageText,
                        'is_read' => fake()->boolean(70), // 70% پیام‌ها خوانده شده
                        'read_at' => fake()->boolean(70) ? fake()->dateTimeBetween('-1 month', 'now') : null,
                        'created_at' => fake()->dateTimeBetween($request->created_at, 'now'),
                    ]);
                }
            }
        }

        // چند پیام تصادفی بین کاربران و مشاوران (بدون درخواست مشاوره)
        for ($i = 0; $i < 30; $i++) {
            $user = $regularUsers->random();
            $consultantUser = $consultantUsers->random();
            $property = $properties->random();

            $messageCount = fake()->numberBetween(1, 4);

            for ($j = 0; $j < $messageCount; $j++) {
                $isFromUser = $j % 2 === 0;

                if ($isFromUser) {
                    $sender = $user;
                    $receiver = $consultantUser;
                    $messageText = fake()->randomElement($userMessages);
                } else {
                    $sender = $consultantUser;
                    $receiver = $user;
                    $messageText = fake()->randomElement($consultantMessages);
                }

                Message::create([
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'property_id' => fake()->boolean(80) ? $property->id : null,
                    'consultation_request_id' => null,
                    'message' => $messageText,
                    'is_read' => fake()->boolean(60),
                    'read_at' => fake()->boolean(60) ? fake()->dateTimeBetween('-1 month', 'now') : null,
                    'created_at' => fake()->dateTimeBetween('-2 months', 'now'),
                ]);
            }
        }

        $this->command->info('پیام‌های تست با موفقیت ایجاد شدند.');
    }
}
