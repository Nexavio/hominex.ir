<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ConsultantUpgradeRequest;
use App\Actions\User\ConsultantUpgradeAction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ConsultantUpgradeController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ConsultantUpgradeAction $consultantUpgradeAction
    ) {}

    /**
     * ارسال درخواست ارتقا به مشاور
     */
    public function submitRequest(ConsultantUpgradeRequest $request): JsonResponse
    {
        $user = auth()->user();

        $result = $this->consultantUpgradeAction->submitRequest(
            $user,
            $request->validated(),
            $request->file('profile_image')
        );

        if ($result['success']) {
            return $this->successResponse(
                data: $result['data'],
                message: $result['message'],
                statusCode: 201
            );
        }

        return $this->errorResponse(
            message: $result['message'],
            statusCode: 400
        );
    }

    /**
     * مشاهده وضعیت درخواست فعلی
     */
    public function getRequestStatus(): JsonResponse
    {
        $user = auth()->user();

        // اگر قبلاً مشاور است
        if ($user->user_type->value === 'consultant') {
            return $this->successResponse([
                'status' => 'approved',
                'is_consultant' => true,
                'consultant_data' => $user->consultant ? [
                    'id' => $user->consultant->id,
                    'company_name' => $user->consultant->company_name,
                    'is_verified' => $user->consultant->is_verified,
                ] : null
            ]);
        }

        // اگر درخواست در انتظار دارد
        if ($user->consultant) {
            return $this->successResponse([
                'status' => 'pending',
                'is_consultant' => false,
                'request_data' => [
                    'id' => $user->consultant->id,
                    'company_name' => $user->consultant->company_name,
                    'submitted_at' => $user->consultant->created_at->toISOString(),
                    'is_verified' => false,
                ]
            ]);
        }

        // هیچ درخواستی ندارد
        return $this->successResponse([
            'status' => 'none',
            'is_consultant' => false,
            'can_request' => $user->canRequestConsultantUpgrade()
        ]);
    }
}
