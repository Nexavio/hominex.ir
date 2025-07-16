<?php

namespace App\Http\Controllers\Api\Property;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\ComparisonSession;
use App\Models\ComparisonItem;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    use ApiResponse;

    /**
     * لیست املاک در مقایسه
     */
    public function index(Request $request): JsonResponse
    {
        $session = $this->getOrCreateComparisonSession($request);

        $comparisonItems = $session->items()
            ->with([
                'property.propertyType',
                'property.images',
                'property.consultant.user',
                'property.createdByUser',
                'property.amenities'
            ])
            ->orderBy('display_order')
            ->get();

        if ($comparisonItems->isEmpty()) {
            return $this->successResponse([
                'session_id' => $session->id,
                'properties' => [],
                'count' => 0,
                'max_items' => config('app.max_comparison_items', 4),
            ], 'لیست مقایسه خالی است.');
        }

        $properties = $comparisonItems->map(function ($item) {
            $property = $item->property;
            return [
                'comparison_item_id' => $item->id,
                'display_order' => $item->display_order,
                'added_at' => $item->added_at->toISOString(),
                'property' => [
                    'id' => $property->id,
                    'title' => $property->title,
                    'property_type' => $property->propertyType->name,
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
                        'direction' => $property->direction,
                        'document_type' => $property->document_type,
                    ],
                    'location' => [
                        'province' => $property->province,
                        'city' => $property->city,
                        'address' => $property->address,
                    ],
                    'amenities' => $property->amenities->pluck('name')->toArray(),
                    'amenities_count' => $property->amenities->count(),
                    'primary_image_url' => $property->primary_image_url,
                    'creator_name' => $property->creator_display_name,
                    'consultant_company' => $property->consultant?->company_name,
                    'views_count' => $property->views_count,
                    'is_featured' => $property->is_featured_active,
                    'published_at' => $property->published_at?->toISOString(),
                ]
            ];
        });

        return $this->successResponse([
            'session_id' => $session->id,
            'properties' => $properties,
            'count' => $comparisonItems->count(),
            'max_items' => config('app.max_comparison_items', 4),
            'comparison_matrix' => $this->generateComparisonMatrix($comparisonItems),
        ], 'لیست املاک مقایسه دریافت شد.');
    }

    /**
     * اضافه کردن ملک به مقایسه
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id'
        ]);

        $property = Property::findOrFail($request->property_id);

        // بررسی اینکه ملک منتشر شده باشد
        if (!$property->is_published) {
            return $this->errorResponse(
                message: 'این ملک در دسترس نیست.',
                statusCode: 404
            );
        }

        $session = $this->getOrCreateComparisonSession($request);

        // بررسی محدودیت تعداد املاک
        $maxItems = config('app.max_comparison_items', 4);
        $currentCount = $session->items()->count();

        if ($currentCount >= $maxItems) {
            return $this->errorResponse(
                message: "حداکثر {$maxItems} ملک می‌توانید مقایسه کنید.",
                statusCode: 400
            );
        }

        // بررسی اینکه قبلاً اضافه نشده باشد
        $existingItem = $session->items()
            ->where('property_id', $property->id)
            ->first();

        if ($existingItem) {
            return $this->errorResponse(
                message: 'این ملک قبلاً به لیست مقایسه اضافه شده است.',
                statusCode: 400
            );
        }

        $comparisonItem = ComparisonItem::create([
            'session_id' => $session->id,
            'property_id' => $property->id,
            'display_order' => $currentCount + 1,
        ]);

        return $this->successResponse([
            'comparison_item_id' => $comparisonItem->id,
            'property_id' => $property->id,
            'session_id' => $session->id,
            'display_order' => $comparisonItem->display_order,
            'count' => $session->items()->count(),
            'added_at' => $comparisonItem->added_at->toISOString(),
        ], 'ملک به لیست مقایسه اضافه شد.');
    }

    /**
     * حذف ملک از مقایسه
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id'
        ]);

        $session = $this->getOrCreateComparisonSession($request);

        $comparisonItem = $session->items()
            ->where('property_id', $request->property_id)
            ->first();

        if (!$comparisonItem) {
            return $this->errorResponse(
                message: 'این ملک در لیست مقایسه وجود ندارد.',
                statusCode: 404
            );
        }

        $comparisonItem->delete();

        // بازسازی ترتیب نمایش
        $this->reorderComparisonItems($session);

        return $this->successResponse([
            'property_id' => $request->property_id,
            'session_id' => $session->id,
            'count' => $session->items()->count(),
            'removed_at' => now()->toISOString(),
        ], 'ملک از لیست مقایسه حذف شد.');
    }

    /**
     * پاک کردن کل لیست مقایسه
     */
    public function clear(Request $request): JsonResponse
    {
        $session = $this->getOrCreateComparisonSession($request);
        $session->items()->delete();

        return $this->successResponse([
            'session_id' => $session->id,
            'cleared_at' => now()->toISOString(),
        ], 'لیست مقایسه پاک شد.');
    }

    /**
     * دریافت یا ایجاد session مقایسه
     */
    private function getOrCreateComparisonSession(Request $request): ComparisonSession
    {
        $userId = auth()->id();
        $deviceFingerprint = $request->header('Device-Fingerprint',
            $request->ip() . '|' . $request->userAgent());

        $session = ComparisonSession::where(function ($query) use ($userId, $deviceFingerprint) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('device_fingerprint', $deviceFingerprint);
            }
        })
        ->where('is_active', true)
        ->where('expires_at', '>', now())
        ->first();

        if (!$session) {
            $session = ComparisonSession::create([
                'user_id' => $userId,
                'device_fingerprint' => $deviceFingerprint,
                'is_active' => true,
                'expires_at' => now()->addDays(7), // انقضای 7 روزه
            ]);
        }

        return $session;
    }

    /**
     * بازسازی ترتیب نمایش آیتم‌ها
     */
    private function reorderComparisonItems(ComparisonSession $session): void
    {
        $items = $session->items()->orderBy('display_order')->get();

        foreach ($items as $index => $item) {
            $item->update(['display_order' => $index + 1]);
        }
    }

    /**
     * تولید ماتریس مقایسه
     */
    private function generateComparisonMatrix($comparisonItems): array
    {
        if ($comparisonItems->count() < 2) {
            return [];
        }

        $fields = [
            'property_type' => 'نوع ملک',
            'property_status' => 'نوع معامله',
            'total_price' => 'قیمت کل',
            'monthly_rent' => 'اجاره ماهانه',
            'rent_deposit' => 'ودیعه',
            'land_area' => 'متراژ',
            'rooms_count' => 'تعداد اتاق',
            'bathrooms_count' => 'تعداد سرویس',
            'building_year' => 'سال ساخت',
            'direction' => 'جهت',
            'city' => 'شهر',
            'document_type' => 'نوع سند',
        ];

        $matrix = [];

        foreach ($fields as $field => $label) {
            $row = ['field' => $field, 'label' => $label, 'values' => []];

            foreach ($comparisonItems as $item) {
                $property = $item->property;
                $value = $this->getPropertyFieldValue($property, $field);
                $row['values'][] = $value;
            }

            $matrix[] = $row;
        }

        return $matrix;
    }

    /**
     * دریافت مقدار فیلد ملک
     */
    private function getPropertyFieldValue($property, string $field)
    {
        return match ($field) {
            'property_type' => $property->propertyType->name,
            'property_status' => $property->property_status_label,
            'total_price' => $property->total_price ? number_format($property->total_price) . ' تومان' : '-',
            'monthly_rent' => $property->monthly_rent ? number_format($property->monthly_rent) . ' تومان' : '-',
            'rent_deposit' => $property->rent_deposit ? number_format($property->rent_deposit) . ' تومان' : '-',
            'land_area' => $property->land_area ? $property->land_area . ' متر' : '-',
            'rooms_count' => $property->rooms_count ?: '-',
            'bathrooms_count' => $property->bathrooms_count ?: '-',
            'building_year' => $property->building_year ?: '-',
            'direction' => $property->direction ?: '-',
            'city' => $property->city ?: '-',
            'document_type' => $property->document_type ?: '-',
            default => '-',
        };
    }
}
