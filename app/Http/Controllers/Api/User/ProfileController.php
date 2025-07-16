<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    use ApiResponse;

    /**
     * نمایش پروفایل کاربر
     */
    public function show(): JsonResponse
    {
        $user = auth()->user();
        $user->load(['consultant', 'createdProperties', 'favorites']);

        return $this->successResponse([
            'id' => $user->id,
            'phone' => $user->phone,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'user_type' => $user->user_type->value,
            'is_active' => $user->is_active,
            'phone_verified_at' => $user->phone_verified_at?->toISOString(),
            'created_at' => $user->created_at->toISOString(),
            'consultant' => $user->consultant ? [
                'id' => $user->consultant->id,
                'company_name' => $user->consultant->company_name,
                'bio' => $user->consultant->bio,
                'contact_phone' => $user->consultant->contact_phone,
                'is_verified' => $user->consultant->is_verified,
                'profile_image_url' => $user->consultant->profile_image_url,
            ] : null,
            'stats' => [
                'created_properties_count' => $user->created_properties_count,
                'approved_properties_count' => $user->approved_properties_count,
                'pending_properties_count' => $user->pending_properties_count,
                'favorites_count' => $user->favorites->count(),
                'unread_notifications_count' => $user->unread_notifications_count,
            ]
        ], 'اطلاعات پروفایل با موفقیت دریافت شد.');
    }

    /**
     * بروزرسانی پروفایل کاربر
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();

        $updateData = array_filter([
            'full_name' => $request->full_name,
            'email' => $request->email,
        ], fn($value) => $value !== null);

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        return $this->successResponse([
            'id' => $user->id,
            'phone' => $user->phone,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'user_type' => $user->user_type->value,
            'updated_at' => $user->updated_at->toISOString(),
        ], 'پروفایل با موفقیت بروزرسانی شد.');
    }
}
