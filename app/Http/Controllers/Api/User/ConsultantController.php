<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\ConsultationRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultantController extends Controller
{
    use ApiResponse;

    /**
     * داشبورد مشاور
     */
    public function dashboard(): JsonResponse
    {
        $user = auth()->user();
        $consultant = $user->consultant;

        if (!$consultant) {
            return $this->errorResponse(
                message: 'اطلاعات مشاور یافت نشد.',
                statusCode: 404
            );
        }

        // آمار املاک
        $propertiesStats = [
            'total' => $consultant->properties()->count(),
            'approved' => $consultant->properties()->where('status', 'approved')->count(),
            'pending' => $consultant->properties()->where('status', 'pending')->count(),
            'draft' => $consultant->properties()->where('status', 'draft')->count(),
            'featured' => $consultant->properties()->where('is_featured', true)->count(),
        ];

        // آمار درخواست‌های مشاوره
        $consultationStats = [
            'total' => $consultant->consultationRequests()->count(),
            'pending' => $consultant->consultationRequests()->where('status', 'pending')->count(),
            'in_progress' => $consultant->consultationRequests()->where('status', 'in_progress')->count(),
            'completed' => $consultant->consultationRequests()->where('status', 'completed')->count(),
            'this_month' => $consultant->consultationRequests()
                ->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        // آمار بازدیدها
        $totalViews = $consultant->properties()->sum('views_count');

        // آخرین املاک
        $recentProperties = $consultant->properties()
            ->with(['propertyType', 'images'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($property) {
                return [
                    'id' => $property->id,
                    'title' => $property->title,
                    'property_type' => $property->propertyType->name,
                    'status' => $property->status,
                    'status_label' => $property->status_label,
                    'views_count' => $property->views_count,
                    'primary_image_url' => $property->primary_image_url,
                    'created_at' => $property->created_at->toISOString(),
                ];
            });

        // آخرین درخواست‌های مشاوره
        $recentConsultations = $consultant->consultationRequests()
            ->with(['property', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($consultation) {
                return [
                    'id' => $consultation->id,
                    'full_name' => $consultation->full_name,
                    'phone' => $consultation->phone,
                    'property_title' => $consultation->property?->title,
                    'status' => $consultation->status,
                    'status_label' => $consultation->status_label,
                    'message' => $consultation->message,
                    'created_at' => $consultation->created_at->toISOString(),
                ];
            });

        return $this->successResponse([
            'consultant_info' => [
                'id' => $consultant->id,
                'company_name' => $consultant->company_name,
                'bio' => $consultant->bio,
                'is_verified' => $consultant->is_verified,
                'profile_image_url' => $consultant->profile_image_url,
                'contact_info' => $consultant->getContactInfo(),
            ],
            'stats' => [
                'properties' => $propertiesStats,
                'consultations' => $consultationStats,
                'total_views' => $totalViews,
            ],
            'recent_properties' => $recentProperties,
            'recent_consultations' => $recentConsultations,
        ], 'داشبورد مشاور دریافت شد.');
    }

    /**
     * لیست املاک مشاور
     */
    public function properties(Request $request): JsonResponse
    {
        $user = auth()->user();
        $consultant = $user->consultant;

        if (!$consultant) {
            return $this->errorResponse(
                message: 'اطلاعات مشاور یافت نشد.',
                statusCode: 404
            );
        }

        $query = $consultant->properties()
            ->with(['propertyType', 'images', 'amenities'])
            ->orderBy('created_at', 'desc');

        // فیلتر بر اساس وضعیت
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
                    'land_area' => $property->land_area,
                    'rooms_count' => $property->rooms_count,
                    'city' => $property->city,
                    'status' => $property->status,
                    'status_label' => $property->status_label,
                    'views_count' => $property->views_count,
                    'is_featured' => $property->is_featured_active,
                    'primary_image_url' => $property->primary_image_url,
                    'images_count' => $property->images->count(),
                    'amenities_count' => $property->amenities->count(),
                    'published_at' => $property->published_at?->toISOString(),
                    'created_at' => $property->created_at->toISOString(),
                ];
            }),
            'pagination' => [
                'current_page' => $properties->currentPage(),
                'total_pages' => $properties->lastPage(),
                'total' => $properties->total(),
                'per_page' => $properties->perPage(),
            ]
        ], 'لیست املاک مشاور دریافت شد.');
    }

    /**
     * لیست درخواست‌های مشاوره
     */
    public function consultationRequests(Request $request): JsonResponse
    {
        $user = auth()->user();
        $consultant = $user->consultant;

        if (!$consultant) {
            return $this->errorResponse(
                message: 'اطلاعات مشاور یافت نشد.',
                statusCode: 404
            );
        }

        $query = $consultant->consultationRequests()
            ->with(['property', 'user'])
            ->orderBy('created_at', 'desc');

        // فیلتر بر اساس وضعیت
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $consultations = $query->paginate($request->get('per_page', 20));

        return $this->successResponse([
            'consultations' => $consultations->map(function ($consultation) {
                return [
                    'id' => $consultation->id,
                    'full_name' => $consultation->full_name,
                    'phone' => $consultation->phone,
                    'message' => $consultation->message,
                    'preferred_contact_method' => $consultation->preferred_contact_method,
                    'preferred_contact_time' => $consultation->preferred_contact_time,
                    'status' => $consultation->status,
                    'status_label' => $consultation->status_label,
                    'consultant_notes' => $consultation->consultant_notes,
                    'property' => $consultation->property ? [
                        'id' => $consultation->property->id,
                        'title' => $consultation->property->title,
                        'property_type' => $consultation->property->propertyType->name,
                        'city' => $consultation->property->city,
                        'primary_image_url' => $consultation->property->primary_image_url,
                    ] : null,
                    'user' => $consultation->user ? [
                        'id' => $consultation->user->id,
                        'full_name' => $consultation->user->full_name,
                        'email' => $consultation->user->email,
                    ] : null,
                    'created_at' => $consultation->created_at->toISOString(),
                ];
            }),
            'pagination' => [
                'current_page' => $consultations->currentPage(),
                'total_pages' => $consultations->lastPage(),
                'total' => $consultations->total(),
                'per_page' => $consultations->perPage(),
            ]
        ], 'لیست درخواست‌های مشاوره دریافت شد.');
    }

    /**
     * بروزرسانی درخواست مشاوره
     */
    public function updateConsultation(Request $request, ConsultationRequest $consultation): JsonResponse
    {
        $user = auth()->user();

        if (!$consultation->canBeUpdatedBy($user)) {
            return $this->errorResponse(
                message: 'شما مجاز به بروزرسانی این درخواست نیستید.',
                statusCode: 403
            );
        }

        $request->validate([
            'status' => 'required|in:pending,contacted,in_progress,completed,cancelled',
            'consultant_notes' => 'nullable|string|max:1000',
        ]);

        $consultation->update([
            'status' => $request->status,
            'consultant_notes' => $request->consultant_notes,
        ]);

        return $this->successResponse([
            'id' => $consultation->id,
            'status' => $consultation->status,
            'status_label' => $consultation->status_label,
            'consultant_notes' => $consultation->consultant_notes,
            'updated_at' => $consultation->updated_at->toISOString(),
        ], 'درخواست مشاوره با موفقیت بروزرسانی شد.');
    }
}
