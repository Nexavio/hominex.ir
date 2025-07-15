<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Actions\User\ConsultantUpgradeAction;
use App\Models\Consultant;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultantController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ConsultantUpgradeAction $consultantUpgradeAction
    ) {}

    /**
     * لیست درخواست‌های ارتقا در انتظار
     */
    public function pendingRequests(): JsonResponse
    {
        $requests = Consultant::with(['user'])
            ->where('is_verified', false)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($consultant) {
                return [
                    'id' => $consultant->id,
                    'user' => [
                        'id' => $consultant->user->id,
                        'full_name' => $consultant->user->full_name,
                        'phone' => $consultant->user->phone,
                        'email' => $consultant->user->email,
                        'created_at' => $consultant->user->created_at->toISOString(),
                    ],
                    'company_name' => $consultant->company_name,
                    'bio' => $consultant->bio,
                    'contact_phone' => $consultant->contact_phone,
                    'contact_whatsapp' => $consultant->contact_whatsapp,
                    'contact_telegram' => $consultant->contact_telegram,
                    'contact_instagram' => $consultant->contact_instagram,
                    'profile_image_url' => $consultant->profile_image ? asset('storage/' . $consultant->profile_image) : null,
                    'submitted_at' => $consultant->created_at->toISOString(),
                ];
            });

        return $this->successResponse([
            'requests' => $requests,
            'total' => $requests->count()
        ]);
    }

    /**
     * جزئیات درخواست ارتقا
     */
    public function show(Consultant $consultant): JsonResponse
    {
        if ($consultant->is_verified) {
            return $this->errorResponse(
                message: 'این درخواست قبلاً تایید شده است.',
                statusCode: 400
            );
        }

        $consultant->load('user');

        return $this->successResponse([
            'id' => $consultant->id,
            'user' => [
                'id' => $consultant->user->id,
                'full_name' => $consultant->user->full_name,
                'phone' => $consultant->user->phone,
                'email' => $consultant->user->email,
                'user_type' => $consultant->user->user_type->value,
                'phone_verified_at' => $consultant->user->phone_verified_at?->toISOString(),
                'created_at' => $consultant->user->created_at->toISOString(),
            ],
            'company_name' => $consultant->company_name,
            'bio' => $consultant->bio,
            'contact_phone' => $consultant->contact_phone,
            'contact_whatsapp' => $consultant->contact_whatsapp,
            'contact_telegram' => $consultant->contact_telegram,
            'contact_instagram' => $consultant->contact_instagram,
            'profile_image_url' => $consultant->profile_image ? asset('storage/' . $consultant->profile_image) : null,
            'submitted_at' => $consultant->created_at->toISOString(),
        ]);
    }

    /**
     * تایید درخواست ارتقا
     */
    public function approve(Consultant $consultant): JsonResponse
    {
        if ($consultant->is_verified) {
            return $this->errorResponse(
                message: 'این درخواست قبلاً تایید شده است.',
                statusCode: 400
            );
        }

        $result = $this->consultantUpgradeAction->approveRequest($consultant);

        if ($result['success']) {
            return $this->successResponse(
                data: $result['data'],
                message: $result['message']
            );
        }

        return $this->errorResponse(
            message: $result['message'],
            statusCode: 500
        );
    }

    /**
     * رد درخواست ارتقا
     */
    public function reject(Request $request, Consultant $consultant): JsonResponse
    {
        if ($consultant->is_verified) {
            return $this->errorResponse(
                message: 'این درخواست قبلاً تایید شده است.',
                statusCode: 400
            );
        }

        $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:255']
        ], [
            'reason.required' => 'دلیل رد الزامی است.',
            'reason.min' => 'دلیل رد باید حداقل 10 کاراکتر باشد.',
        ]);

        $result = $this->consultantUpgradeAction->rejectRequest(
            $consultant,
            $request->reason
        );

        if ($result['success']) {
            return $this->successResponse(
                data: $result['data'],
                message: $result['message']
            );
        }

        return $this->errorResponse(
            message: $result['message'],
            statusCode: 500
        );
    }
}
