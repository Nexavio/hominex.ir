<?php

namespace Database\Seeders;

use App\Models\PropertyAmenity;
use Illuminate\Database\Seeder;

class PropertyAmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            // امکانات ساختمان
            ['name' => 'آسانسور', 'slug' => 'elevator', 'icon' => 'elevator', 'category' => 'building'],
            ['name' => 'پارکینگ', 'slug' => 'parking', 'icon' => 'car', 'category' => 'building'],
            ['name' => 'انباری', 'slug' => 'storage', 'icon' => 'storage', 'category' => 'building'],
            ['name' => 'بالکن', 'slug' => 'balcony', 'icon' => 'balcony', 'category' => 'building'],
            ['name' => 'حیاط', 'slug' => 'yard', 'icon' => 'yard', 'category' => 'building'],
            ['name' => 'سرایدار', 'slug' => 'janitor', 'icon' => 'person', 'category' => 'building'],
            ['name' => 'لابی', 'slug' => 'lobby', 'icon' => 'lobby', 'category' => 'building'],

            // امکانات داخلی
            ['name' => 'کولر گازی', 'slug' => 'ac', 'icon' => 'ac', 'category' => 'interior'],
            ['name' => 'پکیج', 'slug' => 'package', 'icon' => 'heater', 'category' => 'interior'],
            ['name' => 'شومینه', 'slug' => 'fireplace', 'icon' => 'fire', 'category' => 'interior'],
            ['name' => 'کابینت', 'slug' => 'cabinet', 'icon' => 'cabinet', 'category' => 'interior'],
            ['name' => 'کمد دیواری', 'slug' => 'wardrobe', 'icon' => 'wardrobe', 'category' => 'interior'],
            ['name' => 'نور مخفی', 'slug' => 'hidden-light', 'icon' => 'light', 'category' => 'interior'],
            ['name' => 'سقف کاذب', 'slug' => 'false-ceiling', 'icon' => 'ceiling', 'category' => 'interior'],

            // امکانات آشپزخانه
            ['name' => 'هود', 'slug' => 'hood', 'icon' => 'hood', 'category' => 'kitchen'],
            ['name' => 'گاز رومیزی', 'slug' => 'cooktop', 'icon' => 'cooktop', 'category' => 'kitchen'],
            ['name' => 'فر', 'slug' => 'oven', 'icon' => 'oven', 'category' => 'kitchen'],
            ['name' => 'یخچال', 'slug' => 'refrigerator', 'icon' => 'fridge', 'category' => 'kitchen'],
            ['name' => 'ماشین ظرفشویی', 'slug' => 'dishwasher', 'icon' => 'dishwasher', 'category' => 'kitchen'],

            // امکانات حمام
            ['name' => 'جکوزی', 'slug' => 'jacuzzi', 'icon' => 'jacuzzi', 'category' => 'bathroom'],
            ['name' => 'ساونا', 'slug' => 'sauna', 'icon' => 'sauna', 'category' => 'bathroom'],
            ['name' => 'آب گرم مرکزی', 'slug' => 'central-hot-water', 'icon' => 'hot-water', 'category' => 'bathroom'],

            // امکانات رفاهی
            ['name' => 'استخر', 'slug' => 'pool', 'icon' => 'pool', 'category' => 'wellness'],
            ['name' => 'سالن ورزش', 'slug' => 'gym', 'icon' => 'gym', 'category' => 'wellness'],
            ['name' => 'سالن اجتماعات', 'slug' => 'meeting-hall', 'icon' => 'meeting', 'category' => 'wellness'],
            ['name' => 'روف گاردن', 'slug' => 'roof-garden', 'icon' => 'garden', 'category' => 'wellness'],

            // امکانات امنیتی
            ['name' => 'سیستم امنیتی', 'slug' => 'security-system', 'icon' => 'security', 'category' => 'security'],
            ['name' => 'دوربین مداربسته', 'slug' => 'cctv', 'icon' => 'camera', 'category' => 'security'],
            ['name' => 'درب ضد سرقت', 'slug' => 'security-door', 'icon' => 'door', 'category' => 'security'],
            ['name' => 'نگهبانی 24 ساعته', 'slug' => '24h-security', 'icon' => 'guard', 'category' => 'security'],

            // سایر امکانات
            ['name' => 'اینترنت', 'slug' => 'internet', 'icon' => 'wifi', 'category' => 'other'],
            ['name' => 'تلفن', 'slug' => 'phone', 'icon' => 'phone', 'category' => 'other'],
            ['name' => 'کابل تلویزیون', 'slug' => 'cable-tv', 'icon' => 'tv', 'category' => 'other'],
        ];

        foreach ($amenities as $amenity) {
            PropertyAmenity::create(array_merge($amenity, ['is_active' => true]));
        }
    }
}
