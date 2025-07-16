<?php

namespace App\Http\Controllers\Api\Property;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Favorite;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    use ApiResponse;

    /**
     * لیست علاقه‌مندی‌های کاربر
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        $favorites = $user->favorites()
            ->with([
                'property.propertyType',
                'property.images',
                'property.consultant.user',
                'property.createdByUser'
            ])
            ->whereHas('property', function ($query) {
                $query->where('status', 'approved')
                      ->whereNotNull('published_at');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->successResponse([
            'favorites' => $favorites->map(function ($favorite) {
                $property = $favorite->property;
                return [
                    'favorite_id' => $favorite->id,
                    'added_at' => $favorite->created_at->toISOString(),
                    'property' => [
                        'id' => $property->id,
                        'title' => $property->title,
                        'property_type' => $property->propertyType->name,
                        'property_status' => $property->property_status,
                        'property_status_label' => $property->property_status_label,
                        'formatted_price' => $property->formatted_price,
                        'total_price' => $property->total_price,
                        'monthly_rent' => $property->monthly_rent,
                        'land_area' => $property->land_area,
                        'rooms_count' => $property->rooms_count,
                        'city' => $property->city,
                        'province' => $property->province,
                        'views_count' => $property->views_count,
                        'is_featured' => $property->is_featured_active,
                        'primary_image_url' => $property->primary_image_url,
                        'creator_name' => $property->creator_display_name,
                        'consultant_company' => $property->consultant?->company_name,
                        'published_at' => $property->published_at?->toISOString(),
                    ]
                ];
            }),
            'pagination' => [
                'current_page' => $favorites->currentPage(),
                'total_pages' => $favorites->lastPage(),
                'total' => $favorites->total(),
                'per_page' => $favorites->perPage(),
            ]
        ], 'لیست علاقه‌مندی‌های شما دریافت شد.');
    }

    /**
     * اضافه کردن ملک به علاقه‌مندی‌ها
     */
    public function store(Property $property): JsonResponse
    {
        $user = auth()->user();

        // بررسی اینکه ملک منتشر شده باشد
        if (!$property->is_published) {
            return $this->errorResponse(
                message: 'این ملک در دسترس نیست.',
                statusCode: 404
            );
        }

        // بررسی اینکه قبلاً اضافه نشده باشد
        $existingFavorite = Favorite::where('user_id', $user->id)
            ->where('property_id', $property->id)
            ->first();

        if ($existingFavorite) {
            return $this->errorResponse(
                message: 'این ملک قبلاً به علاقه‌مندی‌های شما اضافه شده است.',
                statusCode: 400
            );
        }

        // بررسی محدودیت تعداد علاقه‌مندی‌ها
        $maxFavorites = config('app.max_favorites_per_user', 50);
        $currentFavoritesCount = $user->favorites()->count();

        if ($currentFavoritesCount >= $maxFavorites) {
            return $this->errorResponse(
                message: "حداکثر {$maxFavorites} ملک می‌توانید به علاقه‌مندی‌هایتان اضافه کنید.",
                statusCode: 400
            );
        }

        $favorite = Favorite::create([
            'user_id' => $user->id,
            'property_id' => $property->id,
        ]);

        return $this->successResponse([
            'favorite_id' => $favorite->id,
            'property_id' => $property->id,
            'added_at' => $favorite->created_at->toISOString(),
        ], 'ملک به علاقه‌مندی‌های شما اضافه شد.');
    }

    /**
     * حذف ملک از علاقه‌مندی‌ها
     */
    public function destroy(Property $property): JsonResponse
    {
        $user = auth()->user();

        $favorite = Favorite::where('user_id', $user->id)
            ->where('property_id', $property->id)
            ->first();

        if (!$favorite) {
            return $this->errorResponse(
                message: 'این ملک در علاقه‌مندی‌های شما وجود ندارد.',
                statusCode: 404
            );
        }

        $favorite->delete();

        return $this->successResponse([
            'property_id' => $property->id,
            'removed_at' => now()->toISOString(),
        ], 'ملک از علاقه‌مندی‌های شما حذف شد.');
    }

    /**
     * وضعیت علاقه‌مندی برای یک ملک
     */
    public function status(Property $property): JsonResponse
    {
        $user = auth()->user();

        $isFavorited = Favorite::where('user_id', $user->id)
            ->where('property_id', $property->id)
            ->exists();

        return $this->successResponse([
            'property_id' => $property->id,
            'is_favorited' => $isFavorited,
        ]);
    }

    /**
     * آمار علاقه‌مندی‌های کاربر
     */
    public function stats(): JsonResponse
    {
        $user = auth()->user();

        $stats = [
            'total_favorites' => $user->favorites()->count(),
            'for_sale_count' => $user->favorites()
                ->whereHas('property', function ($query) {
                    $query->where('property_status', 'for_sale');
                })->count(),
            'for_rent_count' => $user->favorites()
                ->whereHas('property', function ($query) {
                    $query->where('property_status', 'for_rent');
                })->count(),
            'recent_favorites' => $user->favorites()
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
        ];

        return $this->successResponse($stats, 'آمار علاقه‌مندی‌های شما دریافت شد.');
    }
}
