<?php

namespace App\Http\Controllers\Api\Property;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyType;
use App\Http\Resources\PropertyResource;
use App\Http\Resources\PropertyCollection;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    use ApiResponse;

    /**
     * لیست املاک (عمومی)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Property::with([
            'propertyType',
            'images',
            'consultant.user',
            'createdByUser',
            'amenities'
        ])
        ->published()
        ->orderBy('is_featured', 'desc')
        ->orderBy('created_at', 'desc');

        // فیلترها
        if ($request->filled('property_type')) {
            $query->where('property_type_id', $request->property_type);
        }

        if ($request->filled('property_status')) {
            $query->where('property_status', $request->property_status);
        }

        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        if ($request->filled('min_price') && $request->filled('max_price')) {
            $query->where(function ($q) use ($request) {
                $q->whereBetween('total_price', [$request->min_price, $request->max_price])
                  ->orWhereBetween('monthly_rent', [$request->min_price, $request->max_price]);
            });
        }

        if ($request->filled('rooms')) {
            $query->where('rooms_count', $request->rooms);
        }

        $properties = $query->paginate($request->get('per_page', 20));

        return $this->successResponse([
            'properties' => $properties->map(function ($property) {
                return [
                    'id' => $property->id,
                    'title' => $property->title,
                    'description' => $property->description,
                    'property_type' => $property->propertyType->name,
                    'property_status' => $property->property_status,
                    'property_status_label' => $property->property_status_label,
                    'formatted_price' => $property->formatted_price,
                    'total_price' => $property->total_price,
                    'monthly_rent' => $property->monthly_rent,
                    'rent_deposit' => $property->rent_deposit,
                    'land_area' => $property->land_area,
                    'rooms_count' => $property->rooms_count,
                    'bathrooms_count' => $property->bathrooms_count,
                    'building_year' => $property->building_year,
                    'city' => $property->city,
                    'province' => $property->province,
                    'direction' => $property->direction,
                    'views_count' => $property->views_count,
                    'is_featured' => $property->is_featured_active,
                    'primary_image_url' => $property->primary_image_url,
                    'images_count' => $property->images->count(),
                    'amenities' => $property->amenities->pluck('name'),
                    'creator_name' => $property->creator_display_name,
                    'consultant_company' => $property->consultant?->company_name,
                    'published_at' => $property->published_at?->toISOString(),
                    'created_at' => $property->created_at->toISOString(),
                ];
            }),
            'pagination' => [
                'current_page' => $properties->currentPage(),
                'total_pages' => $properties->lastPage(),
                'total' => $properties->total(),
                'per_page' => $properties->perPage(),
                'has_more' => $properties->hasMorePages(),
            ]
        ]);
    }

    /**
     * نمایش جزئیات ملک
     */
    public function show(Property $property): JsonResponse
    {
        // بررسی دسترسی
        if (!$property->canBeViewedBy(auth()->user())) {
            return $this->errorResponse(
                message: 'شما مجاز به مشاهده این ملک نیستید.',
                statusCode: 403
            );
        }

        $property->load([
            'propertyType',
            'images' => function ($query) {
                $query->orderBy('display_order');
            },
            'amenities',
            'consultant.user',
            'createdByUser',
            'favorites'
        ]);

        // افزایش تعداد بازدید
        $property->incrementViews();

        $isFavorited = false;
        if (auth()->check()) {
            $isFavorited = $property->favorites()
                ->where('user_id', auth()->id())
                ->exists();
        }

        return $this->successResponse([
            'id' => $property->id,
            'title' => $property->title,
            'description' => $property->description,
            'property_type' => [
                'id' => $property->propertyType->id,
                'name' => $property->propertyType->name,
                'slug' => $property->propertyType->slug,
            ],
            'property_status' => $property->property_status,
            'property_status_label' => $property->property_status_label,
            'price_info' => [
                'total_price' => $property->total_price,
                'monthly_rent' => $property->monthly_rent,
                'rent_deposit' => $property->rent_deposit,
                'formatted_price' => $property->formatted_price,
            ],
            'specifications' => [
                'land_area' => $property->land_area,
                'rooms_count' => $property->rooms_count,
                'bathrooms_count' => $property->bathrooms_count,
                'building_year' => $property->building_year,
                'total_units' => $property->total_units,
                'direction' => $property->direction,
                'document_type' => $property->document_type,
                'usage_type' => $property->usage_type,
            ],
            'location' => [
                'province' => $property->province,
                'city' => $property->city,
                'address' => $property->address,
                'latitude' => $property->latitude,
                'longitude' => $property->longitude,
            ],
            'features' => $property->features,
            'amenities' => $property->amenities->map(function ($amenity) {
                return [
                    'id' => $amenity->id,
                    'name' => $amenity->name,
                    'icon' => $amenity->icon,
                    'category' => $amenity->category,
                ];
            }),
            'images' => $property->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image_url' => $image->full_image_url,
                    'thumbnail_url' => $image->full_thumbnail_url,
                    'is_primary' => $image->is_primary,
                    'display_order' => $image->display_order,
                ];
            }),
            'consultant_info' => [
                'name' => $property->creator_display_name,
                'company_name' => $property->consultant?->company_name,
                'contact_phone' => $property->consultant?->contact_phone,
                'contact_whatsapp' => $property->consultant?->contact_whatsapp,
                'contact_telegram' => $property->consultant?->contact_telegram,
                'is_verified' => $property->consultant?->is_verified ?? false,
                'profile_image_url' => $property->consultant?->profile_image_url,
            ],
            'stats' => [
                'views_count' => $property->views_count,
                'favorites_count' => $property->favorites_count,
                'is_favorited' => $isFavorited,
                'is_featured' => $property->is_featured_active,
            ],
            'status' => $property->status,
            'published_at' => $property->published_at?->toISOString(),
            'created_at' => $property->created_at->toISOString(),
        ]);
    }

    /**
     * ایجاد ملک جدید (فقط برای کاربران احراز هویت شده)
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user->canCreateProperty()) {
            return $this->errorResponse(
                message: 'شما مجاز به ثبت ملک نیستید.',
                statusCode: 403
            );
        }

        // این قسمت در ادامه پیاده‌سازی خواهد شد
        return $this->errorResponse(
            message: 'این قابلیت در حال توسعه است.',
            statusCode: 501
        );
    }

    /**
     * بروزرسانی ملک
     */
    public function update(Request $request, Property $property): JsonResponse
    {
        $user = auth()->user();

        if (!$property->canBeEditedBy($user)) {
            return $this->errorResponse(
                message: 'شما مجاز به ویرایش این ملک نیستید.',
                statusCode: 403
            );
        }

        // این قسمت در ادامه پیاده‌سازی خواهد شد
        return $this->errorResponse(
            message: 'این قابلیت در حال توسعه است.',
            statusCode: 501
        );
    }

    /**
     * حذف ملک
     */
    public function destroy(Property $property): JsonResponse
    {
        $user = auth()->user();

        if (!$property->canBeDeletedBy($user)) {
            return $this->errorResponse(
                message: 'شما مجاز به حذف این ملک نیستید.',
                statusCode: 403
            );
        }

        $property->archive();

        return $this->successResponse(
            message: 'ملک با موفقیت آرشیو شد.'
        );
    }
}
