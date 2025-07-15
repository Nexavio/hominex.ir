<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Property;
use App\Models\Consultant;
use App\Models\ConsultationRequest;
use App\Models\Notification;
use App\Enums\UserRole;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    use ApiResponse;

    /**
     * داشبورد کلی
     */
    public function index(): JsonResponse
    {
        $stats = [
            'users' => $this->getUserStats(),
            'properties' => $this->getPropertyStats(),
            'consultants' => $this->getConsultantStats(),
            'notifications' => $this->getNotificationStats(),
            'recent_activity' => $this->getRecentActivity(),
        ];

        return $this->successResponse($stats);
    }

    /**
     * آمار کاربران
     */
    public function userStats(): JsonResponse
    {
        return $this->successResponse($this->getUserStats());
    }

    /**
     * آمار املاک
     */
    public function propertyStats(): JsonResponse
    {
        return $this->successResponse($this->getPropertyStats());
    }

    /**
     * آمار مشاورین
     */
    public function consultantStats(): JsonResponse
    {
        return $this->successResponse($this->getConsultantStats());
    }

    /**
     * آمار بر اساس بازه زمانی
     */
    public function timeRange(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:users,properties,consultations,notifications'
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $data = match ($request->type) {
            'users' => $this->getUserTimeRangeStats($startDate, $endDate),
            'properties' => $this->getPropertyTimeRangeStats($startDate, $endDate),
            'consultations' => $this->getConsultationTimeRangeStats($startDate, $endDate),
            'notifications' => $this->getNotificationTimeRangeStats($startDate, $endDate),
        };

        return $this->successResponse([
            'type' => $request->type,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'data' => $data
        ]);
    }

    private function getUserStats(): array
    {
        return [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'verified' => User::whereNotNull('phone_verified_at')->count(),
            'by_type' => [
                'regular' => User::where('user_type', UserRole::REGULAR)->count(),
                'consultant' => User::where('user_type', UserRole::CONSULTANT)->count(),
                'admin' => User::where('user_type', UserRole::ADMIN)->count(),
            ],
            'recent_registrations' => User::where('created_at', '>=', now()->subDays(7))->count(),
            'unverified' => User::whereNull('phone_verified_at')->count(),
        ];
    }

    private function getPropertyStats(): array
    {
        return [
            'total' => Property::count(),
            'by_status' => [
                'approved' => Property::where('status', 'approved')->count(),
                'pending' => Property::where('status', 'pending')->count(),
                'rejected' => Property::where('status', 'rejected')->count(),
                'draft' => Property::where('status', 'draft')->count(),
                'archived' => Property::where('status', 'archived')->count(),
            ],
            'by_type' => [
                'for_sale' => Property::where('property_status', 'for_sale')->count(),
                'for_rent' => Property::where('property_status', 'for_rent')->count(),
            ],
            'featured' => Property::where('is_featured', true)->count(),
            'published' => Property::whereNotNull('published_at')->count(),
            'recent_submissions' => Property::where('created_at', '>=', now()->subDays(7))->count(),
            'total_views' => Property::sum('views_count'),
        ];
    }

    private function getConsultantStats(): array
    {
        return [
            'total' => Consultant::count(),
            'verified' => Consultant::where('is_verified', true)->count(),
            'pending_verification' => Consultant::where('is_verified', false)->count(),
            'with_properties' => Consultant::whereHas('properties')->count(),
            'active_consultants' => Consultant::whereHas('user', function ($q) {
                $q->where('is_active', true);
            })->count(),
            'consultation_requests' => [
                'total' => ConsultationRequest::count(),
                'pending' => ConsultationRequest::where('status', 'pending')->count(),
                'in_progress' => ConsultationRequest::where('status', 'in_progress')->count(),
                'completed' => ConsultationRequest::where('status', 'completed')->count(),
            ],
        ];
    }

    private function getNotificationStats(): array
    {
        return [
            'total' => Notification::count(),
            'unread' => Notification::where('is_read', false)->count(),
            'by_type' => Notification::selectRaw('notification_type, COUNT(*) as count')
                ->groupBy('notification_type')
                ->pluck('count', 'notification_type'),
            'by_priority' => Notification::selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority'),
            'recent_24h' => Notification::where('created_at', '>=', now()->subDay())->count(),
        ];
    }

    private function getRecentActivity(): array
    {
        return [
            'recent_users' => User::with('consultant')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'type' => $user->user_type->value,
                    'created_at' => $user->created_at->diffForHumans(),
                ]),
            'recent_properties' => Property::with(['consultant.user', 'createdByUser'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn($property) => [
                    'id' => $property->id,
                    'title' => $property->title,
                    'status' => $property->status,
                    'created_by' => $property->creator_display_name,
                    'created_at' => $property->created_at->diffForHumans(),
                ]),
            'recent_consultant_requests' => Consultant::with('user')
                ->where('is_verified', false)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn($consultant) => [
                    'id' => $consultant->id,
                    'user_name' => $consultant->user->full_name,
                    'company_name' => $consultant->company_name,
                    'created_at' => $consultant->created_at->diffForHumans(),
                ]),
        ];
    }

    private function getUserTimeRangeStats(Carbon $start, Carbon $end): array
    {
        return User::whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getPropertyTimeRangeStats(Carbon $start, Carbon $end): array
    {
        return Property::whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, status')
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->toArray();
    }

    private function getConsultationTimeRangeStats(Carbon $start, Carbon $end): array
    {
        return ConsultationRequest::whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, status')
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->toArray();
    }

    private function getNotificationTimeRangeStats(Carbon $start, Carbon $end): array
    {
        return Notification::whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, notification_type')
            ->groupBy('date', 'notification_type')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->toArray();
    }
}
