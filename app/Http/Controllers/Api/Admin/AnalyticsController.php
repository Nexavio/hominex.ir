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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    use ApiResponse;

    /**
     * داشبورد کلی
     */
    public function index(): JsonResponse
    {
        try {
            $stats = [
                'users' => $this->getUserStats(),
                'properties' => $this->getPropertyStats(),
                'consultants' => $this->getConsultantStats(),
                'notifications' => $this->getNotificationStats(),
                'recent_activity' => $this->getRecentActivity(),
            ];

            return $this->successResponse($stats);
        } catch (\Exception $e) {
            Log::error('Analytics dashboard error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('خطا در دریافت آمار', null, 500);
        }
    }

    /**
     * آمار کاربران
     */
    public function userStats(): JsonResponse
    {
        try {
            return $this->successResponse($this->getUserStats());
        } catch (\Exception $e) {
            Log::error('User stats error: ' . $e->getMessage());
            return $this->errorResponse('خطا در دریافت آمار کاربران', null, 500);
        }
    }

    /**
     * آمار املاک
     */
    public function propertyStats(): JsonResponse
    {
        try {
            return $this->successResponse($this->getPropertyStats());
        } catch (\Exception $e) {
            Log::error('Property stats error: ' . $e->getMessage());
            return $this->errorResponse('خطا در دریافت آمار املاک', null, 500);
        }
    }

    /**
     * آمار مشاورین
     */
    public function consultantStats(): JsonResponse
    {
        try {
            return $this->successResponse($this->getConsultantStats());
        } catch (\Exception $e) {
            Log::error('Consultant stats error: ' . $e->getMessage());
            return $this->errorResponse('خطا در دریافت آمار مشاورین', null, 500);
        }
    }

    /**
     * آمار بر اساس بازه زمانی
     */
    public function timeRange(Request $request): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Time range stats error: ' . $e->getMessage());
            return $this->errorResponse('خطا در دریافت آمار', null, 500);
        }
    }

    private function getUserStats(): array
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Get user stats error: ' . $e->getMessage());
            return [
                'total' => 0,
                'active' => 0,
                'verified' => 0,
                'by_type' => ['regular' => 0, 'consultant' => 0, 'admin' => 0],
                'recent_registrations' => 0,
                'unverified' => 0,
            ];
        }
    }

    private function getPropertyStats(): array
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Get property stats error: ' . $e->getMessage());
            return [
                'total' => 0,
                'by_status' => ['approved' => 0, 'pending' => 0, 'rejected' => 0, 'draft' => 0, 'archived' => 0],
                'by_type' => ['for_sale' => 0, 'for_rent' => 0],
                'featured' => 0,
                'published' => 0,
                'recent_submissions' => 0,
                'total_views' => 0,
            ];
        }
    }

    private function getConsultantStats(): array
    {
        try {
            $consultationRequestsTotal = 0;
            $consultationRequestsPending = 0;
            $consultationRequestsInProgress = 0;
            $consultationRequestsCompleted = 0;

            // بررسی وجود جدول consultation_requests
            if (DB::getSchemaBuilder()->hasTable('consultation_requests')) {
                $consultationRequestsTotal = ConsultationRequest::count();
                $consultationRequestsPending = ConsultationRequest::where('status', 'pending')->count();
                $consultationRequestsInProgress = ConsultationRequest::where('status', 'in_progress')->count();
                $consultationRequestsCompleted = ConsultationRequest::where('status', 'completed')->count();
            }

            return [
                'total' => Consultant::count(),
                'verified' => Consultant::where('is_verified', true)->count(),
                'pending_verification' => Consultant::where('is_verified', false)->count(),
                'with_properties' => Consultant::whereHas('properties')->count(),
                'active_consultants' => Consultant::whereHas('user', function ($q) {
                    $q->where('is_active', true);
                })->count(),
                'consultation_requests' => [
                    'total' => $consultationRequestsTotal,
                    'pending' => $consultationRequestsPending,
                    'in_progress' => $consultationRequestsInProgress,
                    'completed' => $consultationRequestsCompleted,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Get consultant stats error: ' . $e->getMessage());
            return [
                'total' => 0,
                'verified' => 0,
                'pending_verification' => 0,
                'with_properties' => 0,
                'active_consultants' => 0,
                'consultation_requests' => ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0],
            ];
        }
    }

    private function getNotificationStats(): array
    {
        try {
            $byType = [];
            $byPriority = [];

            // استفاده از raw query برای سازگاری بیشتر
            try {
                $byTypeResults = DB::table('notifications')
                    ->select('notification_type', DB::raw('COUNT(*) as count'))
                    ->groupBy('notification_type')
                    ->get();

                foreach ($byTypeResults as $result) {
                    $byType[$result->notification_type] = $result->count;
                }

                $byPriorityResults = DB::table('notifications')
                    ->select('priority', DB::raw('COUNT(*) as count'))
                    ->groupBy('priority')
                    ->get();

                foreach ($byPriorityResults as $result) {
                    $byPriority[$result->priority] = $result->count;
                }
            } catch (\Exception $e) {
                Log::warning('Could not get notification type/priority stats: ' . $e->getMessage());
            }

            return [
                'total' => Notification::count(),
                'unread' => Notification::where('is_read', false)->count(),
                'by_type' => $byType,
                'by_priority' => $byPriority,
                'recent_24h' => Notification::where('created_at', '>=', now()->subDay())->count(),
            ];
        } catch (\Exception $e) {
            Log::error('Get notification stats error: ' . $e->getMessage());
            return [
                'total' => 0,
                'unread' => 0,
                'by_type' => [],
                'by_priority' => [],
                'recent_24h' => 0,
            ];
        }
    }

    private function getRecentActivity(): array
    {
        try {
            $recentUsers = collect();
            $recentProperties = collect();
            $recentConsultantRequests = collect();

            try {
                $recentUsers = User::with('consultant')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(fn($user) => [
                        'id' => $user->id,
                        'name' => $user->full_name,
                        'type' => $user->user_type->value,
                        'created_at' => $user->created_at->diffForHumans(),
                    ]);
            } catch (\Exception $e) {
                Log::warning('Could not get recent users: ' . $e->getMessage());
            }

            try {
                $recentProperties = Property::with(['consultant.user', 'createdByUser'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(fn($property) => [
                        'id' => $property->id,
                        'title' => $property->title,
                        'status' => $property->status,
                        'created_by' => $property->creator_display_name ?? 'نامشخص',
                        'created_at' => $property->created_at->diffForHumans(),
                    ]);
            } catch (\Exception $e) {
                Log::warning('Could not get recent properties: ' . $e->getMessage());
            }

            try {
                $recentConsultantRequests = Consultant::with('user')
                    ->where('is_verified', false)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(fn($consultant) => [
                        'id' => $consultant->id,
                        'user_name' => $consultant->user->full_name ?? 'نامشخص',
                        'company_name' => $consultant->company_name,
                        'created_at' => $consultant->created_at->diffForHumans(),
                    ]);
            } catch (\Exception $e) {
                Log::warning('Could not get recent consultant requests: ' . $e->getMessage());
            }

            return [
                'recent_users' => $recentUsers,
                'recent_properties' => $recentProperties,
                'recent_consultant_requests' => $recentConsultantRequests,
            ];
        } catch (\Exception $e) {
            Log::error('Get recent activity error: ' . $e->getMessage());
            return [
                'recent_users' => [],
                'recent_properties' => [],
                'recent_consultant_requests' => [],
            ];
        }
    }

    private function getUserTimeRangeStats(Carbon $start, Carbon $end): array
    {
        try {
            return User::whereBetween('created_at', [$start, $end])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('User time range stats error: ' . $e->getMessage());
            return [];
        }
    }

    private function getPropertyTimeRangeStats(Carbon $start, Carbon $end): array
    {
        try {
            return Property::whereBetween('created_at', [$start, $end])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count, status')
                ->groupBy('date', 'status')
                ->orderBy('date')
                ->get()
                ->groupBy('date')
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Property time range stats error: ' . $e->getMessage());
            return [];
        }
    }

    private function getConsultationTimeRangeStats(Carbon $start, Carbon $end): array
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('consultation_requests')) {
                return [];
            }

            return ConsultationRequest::whereBetween('created_at', [$start, $end])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count, status')
                ->groupBy('date', 'status')
                ->orderBy('date')
                ->get()
                ->groupBy('date')
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Consultation time range stats error: ' . $e->getMessage());
            return [];
        }
    }

    private function getNotificationTimeRangeStats(Carbon $start, Carbon $end): array
    {
        try {
            return Notification::whereBetween('created_at', [$start, $end])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count, notification_type')
                ->groupBy('date', 'notification_type')
                ->orderBy('date')
                ->get()
                ->groupBy('date')
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Notification time range stats error: ' . $e->getMessage());
            return [];
        }
    }
}
