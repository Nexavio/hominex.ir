<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyManagementController extends Controller
{
    use ApiResponse;

    /**
     * لیست همه املاک (ادمین)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Property::with([
            'propertyType',
            'images',
            'consultant.user',
            'createdByUser',
            'amenities'
        ])->orderBy('created_at', 'desc');

        // فیلتر بر اساس وضعیت
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فیلتر بر اساس نوع ملک
        if ($request->filled('property_type')) {
            $query->where('property_type_id', $request->property_type);
        }

        // فیلتر بر اساس نوع معامله
        if ($request->filled('property_status')) {
            $query->where('property_status', $request->property_status);
        }

        // فیلتر بر اساس شهر
        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        // جستجو در عنوان
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // فیلتر بر اساس مشاور
        if ($request->filled('consultant_id')) {
            $query->where('consultant_id', $request->consultant_id);
        }

        // فیلتر املاک ویژه
        if ($request->filled('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        $properties = $query->paginate($request->get('per_page', 20));

        return $this->successResponse([
            'properties' => $properties->map(function ($property) {
                return [
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
                    'status' => $property->status,
                    'status_label' => $property->status_label,
                    'rejection_reason' => $property->rejection_reason,
                    'views_count' => $property->views_count,
                    'is_featured' => $property->is_featured,
                    'featured_until' => $property->featured_until?->toISOString(),
                    'primary_image_url' => $property->primary_image_url,
                    'images_count' => $property->images->count(),
                    'amenities_count' => $property->amenities->count(),
                    'creator_info' => [
                        'name' => $property->creator_display_name,
                        'type' => $property->is_created_by_regular_user ? 'user' : 'consultant',
                        'company_name' => $property->consultant?->company_name,
                        'user_id' => $property->created_by_user_id,
                        'consultant_id' => $property->consultant_id,
                    ],
                    'published_at' => $property->published_at?->toISOString(),
                    'created_at' => $property->created_at->toISOString(),
                    'updated_at' => $property->updated_at->toISOString(),
                ];
            }),
            'pagination' => [
                'current_page' => $properties->currentPage(),
                'total_pages' => $properties->lastPage(),
                'total' => $properties->total(),
                'per_page' => $properties->perPage(),
            ],
            'stats' => [
                'total' => Property::count(),
                'pending' => Property::where('status', 'pending')->count(),
                'approved' => Property::where('status', 'approved')->count(),
                'rejected' => Property::where('status', 'rejected')->count(),
                'featured' => Property::where('is_featured', true)->count(),
            ]
        ]);
    }

    /**
     * نمایش جزئیات ملک (ادمین)
     */
    public function show(Property $property): JsonResponse
    {
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
            'creator_info' => [
                'name' => $property->creator_display_name,
                'type' => $property->is_created_by_regular_user ? 'user' : 'consultant',
                'user_id' => $property->created_by_user_id,
                'consultant_id' => $property->consultant_id,
                'user_details' => $property->createdByUser ? [
                    'id' => $property->createdByUser->id,
                    'full_name' => $property->createdByUser->full_name,
                    'phone' => $property->createdByUser->phone,
                    'email' => $property->createdByUser->email,
                ] : null,
                'consultant_details' => $property->consultant ? [
                    'id' => $property->consultant->id,
                    'company_name' => $property->consultant->company_name,
                    'contact_phone' => $property->consultant->contact_phone,
                    'is_verified' => $property->consultant->is_verified,
                ] : null,
            ],
            'admin_info' => [
                'status' => $property->status,
                'status_label' => $property->status_label,
                'rejection_reason' => $property->rejection_reason,
                'is_featured' => $property->is_featured,
                'featured_until' => $property->featured_until?->toISOString(),
                'views_count' => $property->views_count,
                'favorites_count' => $property->favorites_count,
                'published_at' => $property->published_at?->toISOString(),
            ],
            'created_at' => $property->created_at->toISOString(),
            'updated_at' => $property->updated_at->toISOString(),
        ]);
    }

    /**
     * تایید ملک
     */
    public function approve(Property $property): JsonResponse
    {
        if ($property->status === 'approved') {
            return $this->errorResponse(
                message: 'این ملک قبلاً تایید شده است.',
                statusCode: 400
            );
        }

        $property->approve();

        return $this->successResponse([
            'property_id' => $property->id,
            'status' => $property->status,
            'published_at' => $property->published_at->toISOString(),
        ], 'ملک با موفقیت تایید و منتشر شد.');
    }

    /**
     * رد ملک
     */
    public function reject(Request $request, Property $property): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:255'
        ], [
            'reason.required' => 'دلیل رد الزامی است.',
            'reason.min' => 'دلیل رد باید حداقل 10 کاراکتر باشد.',
        ]);

        if ($property->status === 'rejected') {
            return $this->errorResponse(
                message: 'این ملک قبلاً رد شده است.',
                statusCode: 400
            );
        }

        $property->reject($request->reason);

        return $this->successResponse([
            'property_id' => $property->id,
            'status' => $property->status,
            'rejection_reason' => $property->rejection_reason,
        ], 'ملک با موفقیت رد شد.');
    }

    /**
     * ویژه کردن ملک
     */
    public function feature(Request $request, Property $property): JsonResponse
    {
        $request->validate([
            'days' => 'nullable|integer|min:1|max:365'
        ]);

        $days = $request->get('days', 30);
        $property->feature($days);

        return $this->successResponse([
            'property_id' => $property->id,
            'is_featured' => $property->is_featured,
            'featured_until' => $property->featured_until?->toISOString(),
        ], "ملک برای {$days} روز ویژه شد.");
    }

    /**
     * حذف ویژگی ملک
     */
    public function unfeature(Property $property): JsonResponse
    {
        $property->unfeature();

        return $this->successResponse([
            'property_id' => $property->id,
            'is_featured' => $property->is_featured,
        ], 'ویژگی ملک حذف شد.');
    }

    /**
     * آرشیو ملک
     */
    public function destroy(Property $property): JsonResponse
    {
        $property->archive();

        return $this->successResponse([
            'property_id' => $property->id,
            'status' => $property->status,
        ], 'ملک با موفقیت آرشیو شد.');
    }

    /**
     * آمار املاک ادمین
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Property::count(),
            'by_status' => [
                'draft' => Property::where('status', 'draft')->count(),
                'pending' => Property::where('status', 'pending')->count(),
                'approved' => Property::where('status', 'approved')->count(),
                'rejected' => Property::where('status', 'rejected')->count(),
                'archived' => Property::where('status', 'archived')->count(),
            ],
            'by_property_status' => [
                'for_sale' => Property::where('property_status', 'for_sale')->count(),
                'for_rent' => Property::where('property_status', 'for_rent')->count(),
            ],
            'featured_count' => Property::where('is_featured', true)->count(),
            'published_count' => Property::whereNotNull('published_at')->count(),
            'total_views' => Property::sum('views_count'),
            'recent_pending' => Property::where('status', 'pending')
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
            'by_creator_type' => [
                'regular_users' => Property::where('consultant_id', 1)->count(),
                'consultants' => Property::where('consultant_id', '>', 1)->count(),
            ],
            'monthly_stats' => Property::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->pluck('count', 'month'),
        ];

        return $this->successResponse($stats);
    }
}
