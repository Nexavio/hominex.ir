<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Consultant;
use App\Models\PropertyAmenity;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        $consultants = Consultant::all();
        $propertyTypes = PropertyType::all();
        $amenities = PropertyAmenity::all();
        $regularUsers = User::where('user_type', UserRole::REGULAR)->get();

        if ($consultants->isEmpty() || $propertyTypes->isEmpty()) {
            $this->command->info('ابتدا باید ConsultantSeeder و PropertyTypeSeeder اجرا شوند.');
            return;
        }

        // مطمئن شویم که مشاور پیش‌فرض (ID=1) وجود دارد
        $defaultConsultant = $consultants->where('id', 1)->first();
        if (!$defaultConsultant) {
            $this->command->error('مشاور پیش‌فرض (ID=1) وجود ندارد!');
            return;
        }

        $provinces = ['تهران', 'اصفهان', 'شیراز', 'مشهد', 'تبریز', 'کرج', 'اهواز', 'کرمان'];
        $tehranDistricts = [
            'منطقه 1', 'منطقه 2', 'منطقه 3', 'منطقه 4', 'منطقه 5',
            'منطقه 6', 'منطقه 7', 'منطقه 8', 'منطقه 9', 'منطقه 10',
            'منطقه 11', 'منطقه 12', 'منطقه 13', 'منطقه 14', 'منطقه 15',
            'منطقه 16', 'منطقه 17', 'منطقه 18', 'منطقه 19', 'منطقه 20', 'منطقه 22'
        ];

        $directions = ['شمالی', 'جنوبی', 'شرقی', 'غربی', 'شمال شرقی', 'شمال غربی', 'جنوب شرقی', 'جنوب غربی'];
        $documentTypes = ['سند تک برگ', 'سند دو برگ', 'سند اداری', 'قولنامه‌ای', 'وکالتی'];
        $usageTypes = ['مسکونی', 'تجاری', 'اداری', 'صنعتی', 'کشاورزی'];

        $propertyDescriptions = [
            'آپارتمان نوساز با امکانات کامل در بهترین نقطه شهر',
            'ویلای لوکس با حیاط سبز و چشم‌انداز زیبا',
            'خانه ویلایی قدیمی قابل بازسازی در محله آرام',
            'زمین مسکونی با کاربری تجاری در خیابان اصلی',
            'مغازه پر رفت و آمد با موقعیت تجاری عالی',
            'دفتر کار مدرن با امکانات روز دنیا',
            'انبار بزرگ با دسترسی آسان به بزرگراه',
            'باغ میوه با چاه آب و ویلای کوچک',
        ];

        // ایجاد 100 ملک
        for ($i = 0; $i < 100; $i++) {
            $propertyType = $propertyTypes->random();
            $province = fake()->randomElement($provinces);
            $city = $province === 'تهران' ? fake()->randomElement($tehranDistricts) : $province;

            $propertyStatus = fake()->randomElement(['for_sale', 'for_rent']);
            $status = fake()->randomElement(['draft', 'pending', 'approved', 'rejected']);

            // تعیین اینکه این آگهی توسط مشاور یا کاربر معمولی ثبت شده
            $isCreatedByConsultant = fake()->boolean(70); // 70% توسط مشاوران واقعی

            if ($isCreatedByConsultant) {
                // آگهی توسط مشاور واقعی ثبت شده
                $consultant = $consultants->where('id', '>', 1)->random(); // مشاوران واقعی (ID > 1)
                $consultantId = $consultant->id;
                $createdByUserId = $consultant->user_id;
            } else {
                // آگهی توسط کاربر معمولی ثبت شده (با مشاور پیش‌فرض)
                $consultantId = 1; // مشاور پیش‌فرض
                $createdByUserId = $regularUsers->isNotEmpty() ? $regularUsers->random()->id : 1;
            }

            // قیمت‌گذاری بر اساس نوع ملک و وضعیت
            $totalPrice = null;
            $rentDeposit = null;
            $monthlyRent = null;

            if ($propertyStatus === 'for_sale') {
                $totalPrice = fake()->numberBetween(500000000, 15000000000); // 500 میلیون تا 15 میلیارد
            } else {
                $rentDeposit = fake()->numberBetween(50000000, 2000000000); // 50 میلیون تا 2 میلیارد
                $monthlyRent = fake()->numberBetween(5000000, 100000000); // 5 میلیون تا 100 میلیون
            }

            $property = Property::create([
                'consultant_id' => $consultantId,
                'created_by_user_id' => $createdByUserId, // فیلد جدید
                'property_type_id' => $propertyType->id,
                'title' => $propertyType->name . ' ' . fake()->numberBetween(50, 300) . ' متری در ' . $city,
                'description' => fake()->randomElement($propertyDescriptions) . '. ' . fake()->sentence(10),
                'property_status' => $propertyStatus,
                'total_price' => $totalPrice,
                'rent_deposit' => $rentDeposit,
                'monthly_rent' => $monthlyRent,
                'land_area' => fake()->numberBetween(80, 1000),
                'building_year' => fake()->numberBetween(1380, 1403),
                'rooms_count' => fake()->numberBetween(1, 5),
                'bathrooms_count' => fake()->numberBetween(1, 3),
                'document_type' => fake()->randomElement($documentTypes),
                'total_units' => fake()->numberBetween(1, 8),
                'usage_type' => fake()->randomElement($usageTypes),
                'direction' => fake()->randomElement($directions),
                'latitude' => fake()->latitude(35.6, 35.8), // حدود تهران
                'longitude' => fake()->longitude(51.2, 51.6), // حدود تهران
                'province' => $province,
                'city' => $city,
                'address' => 'خیابان ' . fake()->streetName() . '، پلاک ' . fake()->buildingNumber(),
                'features' => [
                    'نورگیر عالی',
                    'دسترسی آسان به مترو',
                    'نزدیک به مراکز خرید',
                    'محله آرام و امن',
                    'پارکینگ اختصاصی'
                ],
                'status' => $status,
                'rejection_reason' => $status === 'rejected' ? 'عدم تطابق مدارک با اطلاعات ارائه شده' : null,
                'views_count' => fake()->numberBetween(0, 500),
                'is_featured' => fake()->boolean(20), // 20% احتمال ویژه بودن
                'featured_until' => fake()->boolean(20) ? now()->addDays(fake()->numberBetween(1, 30)) : null,
                'published_at' => $status === 'approved' ? now()->subDays(fake()->numberBetween(0, 90)) : null,
            ]);

            // اضافه کردن امکانات تصادفی
            $randomAmenities = $amenities->random(fake()->numberBetween(3, 10));
            $property->amenities()->attach($randomAmenities->pluck('id'));
        }

        $this->command->info('100 ملک با created_by_user_id با موفقیت ایجاد شد.');
    }
}
