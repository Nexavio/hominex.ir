<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Property;
use App\Models\Favorite;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    public function run(): void
    {
        $regularUsers = User::where('user_type', UserRole::REGULAR)->get();
        $approvedProperties = Property::where('status', 'approved')->get();

        if ($regularUsers->isEmpty() || $approvedProperties->isEmpty()) {
            $this->command->info('ابتدا باید کاربران عادی و املاک تأیید شده وجود داشته باشند.');
            return;
        }

        // هر کاربر بین 0 تا 15 ملک را به علاقه‌مندی‌هایش اضافه کند
        foreach ($regularUsers as $user) {
            $favoriteCount = fake()->numberBetween(0, 15);

            if ($favoriteCount > 0) {
                $randomProperties = $approvedProperties->random(min($favoriteCount, $approvedProperties->count()));

                foreach ($randomProperties as $property) {
                    // چک کنیم که قبلاً اضافه نشده باشد
                    if (!Favorite::where('user_id', $user->id)->where('property_id', $property->id)->exists()) {
                        Favorite::create([
                            'user_id' => $user->id,
                            'property_id' => $property->id,
                        ]);
                    }
                }
            }
        }

        $this->command->info('علاقه‌مندی‌های کاربران با موفقیت ایجاد شدند.');
    }
}
