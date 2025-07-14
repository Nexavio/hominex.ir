<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Database\Seeder;

class PropertyImageSeeder extends Seeder
{
    public function run(): void
    {
        $properties = Property::all();

        if ($properties->isEmpty()) {
            $this->command->info('ابتدا باید PropertySeeder اجرا شود.');
            return;
        }

        // URLs نمونه برای تصاویر املاک (از Unsplash)
        $sampleImages = [
            'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800',
            'https://images.unsplash.com/photo-1570129477492-45c003edd2be?w=800',
            'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800',
            'https://images.unsplash.com/photo-1560185127-6ed516415b50?w=800',
            'https://images.unsplash.com/photo-1582268611958-ebfd161ef9cf?w=800',
            'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800',
            'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800',
            'https://images.unsplash.com/photo-1484154218962-a197022b5858?w=800',
            'https://images.unsplash.com/photo-1565182999561-18d7dc61c393?w=800',
            'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800',
            'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800',
            'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?w=800',
            'https://images.unsplash.com/photo-1600573472592-401b489a3cdc?w=800',
            'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800',
            'https://images.unsplash.com/photo-1600566753151-384129cf4e3e?w=800',
            'https://images.unsplash.com/photo-1600563438938-a42d180b9c0e?w=800',
            'https://images.unsplash.com/photo-1599423300746-b62533397364?w=800',
            'https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=800',
            'https://images.unsplash.com/photo-1600566752355-35792bedcfea?w=800',
            'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=800',
        ];

        foreach ($properties as $property) {
            // هر ملک بین 3 تا 8 تصویر داشته باشد
            $imageCount = fake()->numberBetween(3, 8);

            for ($i = 0; $i < $imageCount; $i++) {
                $imageUrl = fake()->randomElement($sampleImages);

                PropertyImage::create([
                    'property_id' => $property->id,
                    'image_url' => $imageUrl,
                    'thumbnail_url' => str_replace('w=800', 'w=300', $imageUrl), // thumbnail کوچکتر
                    'is_primary' => $i === 0, // اولین تصویر اصلی باشد
                    'display_order' => $i + 1,
                ]);
            }
        }

        $this->command->info('تصاویر املاک با موفقیت ایجاد شدند.');
    }
}
