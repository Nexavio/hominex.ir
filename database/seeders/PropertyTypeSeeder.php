<?php

namespace Database\Seeders;

use App\Models\PropertyType;
use Illuminate\Database\Seeder;

class PropertyTypeSeeder extends Seeder
{
    public function run(): void
    {
        $propertyTypes = [
            [
                'name' => 'آپارتمان',
                'slug' => 'apartment',
                'icon' => 'building',
                'is_active' => true,
            ],
            [
                'name' => 'ویلا',
                'slug' => 'villa',
                'icon' => 'home',
                'is_active' => true,
            ],
            [
                'name' => 'خانه',
                'slug' => 'house',
                'icon' => 'house',
                'is_active' => true,
            ],
            [
                'name' => 'زمین',
                'slug' => 'land',
                'icon' => 'map',
                'is_active' => true,
            ],
            [
                'name' => 'مغازه',
                'slug' => 'shop',
                'icon' => 'store',
                'is_active' => true,
            ],
            [
                'name' => 'دفتر کار',
                'slug' => 'office',
                'icon' => 'briefcase',
                'is_active' => true,
            ],
            [
                'name' => 'انبار',
                'slug' => 'warehouse',
                'icon' => 'warehouse',
                'is_active' => true,
            ],
            [
                'name' => 'باغ',
                'slug' => 'garden',
                'icon' => 'tree',
                'is_active' => true,
            ],
        ];

        foreach ($propertyTypes as $type) {
            PropertyType::create($type);
        }
    }
}
