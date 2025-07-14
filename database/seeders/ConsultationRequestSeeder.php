<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Consultant;
use App\Models\Property;
use App\Models\ConsultationRequest;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;

class ConsultationRequestSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('user_type', UserRole::REGULAR)->get();
        $consultants = Consultant::all();
        $properties = Property::where('status', 'approved')->get();

        if ($users->isEmpty() || $consultants->isEmpty() || $properties->isEmpty()) {
            $this->command->info('ابتدا باید کاربران، مشاوران و املاک وجود داشته باشند.');
            return;
        }

        $messages = [
            'سلام، من به این ملک علاقه‌مند هستم. ممکن است اطلاعات بیشتری در مورد آن بدهید؟',
            'با سلام، آیا امکان بازدید از این ملک وجود دارد؟ چه زمانی مناسب است؟',
            'ممکن است قیمت نهایی این ملک را اعلام کنید؟ آیا امکان تخفیف هست؟',
            'سلام، من دنبال خرید این نوع ملک هستم. لطفاً با من تماس بگیرید.',
            'آیا سند این ملک آماده است؟ چقدر زمان برای انتقال نیاز است؟',
            'با سلام، امکان دیدن سایر املاک مشابه را دارید؟',
            'ملک شما جالب توجه است. آیا امکان دیدار حضوری وجود دارد؟',
            'سلام، آیا این ملک هنوز موجود است؟ قیمت آخر چقدر است؟',
            'من خریدار جدی هستم. لطفاً در اسرع وقت تماس بگیرید.',
            'با سلام، آیا امکان پرداخت اقساطی وجود دارد؟',
        ];

        $contactMethods = ['phone', 'whatsapp', 'telegram'];
        $contactTimes = [
            'صبح 8 تا 12',
            'بعدازظهر 14 تا 18',
            'عصر 18 تا 21',
            'تماس در هر زمان',
            'فقط پیامک',
            'فقط عصرها',
            'روزهای کاری',
            'آخر هفته‌ها'
        ];

        $statuses = ['pending', 'contacted', 'in_progress', 'completed', 'cancelled'];
        $statusWeights = [40, 30, 15, 10, 5]; // احتمال هر وضعیت

        $consultantNotes = [
            'مشتری جدی و آماده خرید است.',
            'نیاز به مشاوره بیشتر در مورد وام دارد.',
            'قیمت پیشنهادی مناسب نیست.',
            'منتظر فروش ملک فعلی است.',
            'در حال بررسی گزینه‌های مختلف.',
            'تماس گرفته شد، پاسخگو نبود.',
            'قرار ملاقات تنظیم شد.',
            'مشتری منصرف شده است.',
        ];

        // ایجاد 150 درخواست مشاوره
        for ($i = 0; $i < 150; $i++) {
            $user = $users->random();
            $property = $properties->random();
            $consultant = $property->consultant; // مشاور همان ملک

            $status = fake()->randomElement($statuses, $statusWeights);

            ConsultationRequest::create([
                'user_id' => fake()->boolean(80) ? $user->id : null, // 80% کاربران عضو باشند
                'consultant_id' => $consultant->id,
                'property_id' => fake()->boolean(90) ? $property->id : null, // 90% مربوط به ملک خاص باشند
                'full_name' => $user->full_name ?: fake()->name(),
                'phone' => fake()->boolean(70) ? $user->phone : '0912' . fake()->numberBetween(1000000, 9999999),
                'message' => fake()->randomElement($messages),
                'preferred_contact_method' => fake()->randomElement($contactMethods),
                'preferred_contact_time' => fake()->randomElement($contactTimes),
                'status' => $status,
                'consultant_notes' => in_array($status, ['contacted', 'in_progress', 'completed', 'cancelled'])
                    ? fake()->randomElement($consultantNotes)
                    : null,
                'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
            ]);
        }

        $this->command->info('150 درخواست مشاوره با موفقیت ایجاد شد.');
    }
}
