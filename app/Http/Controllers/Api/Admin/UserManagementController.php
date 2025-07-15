<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Consultant;
use App\Enums\UserRole;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    use ApiResponse;

    /**
     * لیست کاربران
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['consultant', 'createdProperties']);

        // فیلتر بر اساس نوع کاربر
        if ($request->has('user_type')) {
            $query->where('user_type', $request->user_type);
        }

        // فیلتر بر اساس وضعیت فعال/غیرفعال
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // فیلتر بر اساس تایید شماره
        if ($request->has('is_verified')) {
            if ($request->boolean('is_verified')) {
                $query->whereNotNull('phone_verified_at');
            } else {
                $query->whereNull('phone_verified_at');
            }
        }

        // جستجو بر اساس نام یا شماره
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        $userData = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'phone' => $user->phone,
                'email' => $user->email,
                'user_type' => $user->user_type->value,
                'is_active' => $user->is_active,
                'phone_verified_at' => $user->phone_verified_at?->toISOString(),
                'created_at' => $user->created_at->toISOString(),
                'properties_count' => $user->created_properties_count,
                'approved_properties_count' => $user->approved_properties_count,
                'consultant' => $user->consultant ? [
                    'id' => $user->consultant->id,
                    'company_name' => $user->consultant->company_name,
                    'is_verified' => $user->consultant->is_verified,
                ] : null,
            ];
        });

        return $this->successResponse([
            'users' => $userData,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'total_pages' => $users->lastPage(),
                'total' => $users->total(),
                'per_page' => $users->perPage(),
            ]
        ]);
    }

    /**
     * جزئیات کاربر
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['consultant', 'createdProperties', 'favorites', 'consultationRequests']);

        return $this->successResponse([
            'id' => $user->id,
            'full_name' => $user->full_name,
            'phone' => $user->phone,
            'email' => $user->email,
            'user_type' => $user->user_type->value,
            'is_active' => $user->is_active,
            'phone_verified_at' => $user->phone_verified_at?->toISOString(),
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
            'consultant' => $user->consultant ? [
                'id' => $user->consultant->id,
                'company_name' => $user->consultant->company_name,
                'bio' => $user->consultant->bio,
                'contact_phone' => $user->consultant->contact_phone,
                'contact_whatsapp' => $user->consultant->contact_whatsapp,
                'contact_telegram' => $user->consultant->contact_telegram,
                'contact_instagram' => $user->consultant->contact_instagram,
                'is_verified' => $user->consultant->is_verified,
                'created_at' => $user->consultant->created_at->toISOString(),
            ] : null,
            'stats' => [
                'properties_count' => $user->createdProperties->count(),
                'approved_properties' => $user->createdProperties->where('status', 'approved')->count(),
                'pending_properties' => $user->createdProperties->where('status', 'pending')->count(),
                'favorites_count' => $user->favorites->count(),
                'consultation_requests_count' => $user->consultationRequests->count(),
            ]
        ]);
    }

    /**
     * بروزرسانی کاربر
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'full_name' => 'sometimes|string|min:2|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'user_type' => 'sometimes|in:regular,consultant,admin',
            'is_active' => 'sometimes|boolean',
        ]);

        $user->update($request->only(['full_name', 'email', 'user_type', 'is_active']));

        return $this->successResponse([
            'user' => $user->fresh(),
            'message' => 'اطلاعات کاربر بروزرسانی شد.'
        ]);
    }

    /**
     * غیرفعال/فعال کردن کاربر
     */
    public function toggleActive(User $user): JsonResponse
    {
        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'فعال' : 'غیرفعال';

        return $this->successResponse([
            'user_id' => $user->id,
            'is_active' => $user->is_active,
        ], "کاربر {$status} شد.");
    }

    /**
     * تایید شماره تماس کاربر (توسط ادمین)
     */
    public function verifyPhone(User $user): JsonResponse
    {
        if ($user->phone_verified_at) {
            return $this->errorResponse('شماره تماس این کاربر قبلاً تایید شده است.');
        }

        $user->update(['phone_verified_at' => now()]);

        return $this->successResponse([
            'user_id' => $user->id,
            'phone_verified_at' => $user->phone_verified_at->toISOString(),
        ], 'شماره تماس کاربر تایید شد.');
    }

    /**
     * تغییر نقش کاربر
     */
    public function changeRole(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'user_type' => 'required|in:regular,consultant,admin',
            'reason' => 'nullable|string|max:255'
        ]);

        $oldRole = $user->user_type->value;
        $newRole = $request->user_type;

        if ($oldRole === $newRole) {
            return $this->errorResponse('نقش کاربر قبلاً همین است.');
        }

        // اگر داره از مشاور به عادی تبدیل میشه، مشاور رو غیرفعال کن
        if ($oldRole === 'consultant' && $newRole !== 'consultant' && $user->consultant) {
            $user->consultant->update(['is_verified' => false]);
        }

        $user->update(['user_type' => UserRole::from($newRole)]);

        return $this->successResponse([
            'user_id' => $user->id,
            'old_role' => $oldRole,
            'new_role' => $newRole,
            'reason' => $request->reason,
            'changed_at' => now()->toISOString(),
        ], "نقش کاربر از {$oldRole} به {$newRole} تغییر یافت.");
    }

    /**
     * آمار کلی کاربران
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'verified_users' => User::whereNotNull('phone_verified_at')->count(),
            'by_type' => [
                'regular' => User::where('user_type', UserRole::REGULAR)->count(),
                'consultant' => User::where('user_type', UserRole::CONSULTANT)->count(),
                'admin' => User::where('user_type', UserRole::ADMIN)->count(),
            ],
            'recent_registrations' => [
                'today' => User::whereDate('created_at', today())->count(),
                'this_week' => User::where('created_at', '>=', now()->startOfWeek())->count(),
                'this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
            ]
        ];

        return $this->successResponse($stats);
    }
}
