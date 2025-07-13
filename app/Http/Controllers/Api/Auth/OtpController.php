<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OtpRequest;
use App\Services\OtpService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class OtpController extends Controller
{
    use ApiResponse;

    public function __construct(
        private OtpService $otpService
    ) {}

    /**
     * ارسال کد OTP جدید
     */
    public function send(OtpRequest $request): JsonResponse
    {
        $purpose = $request->input('purpose', 'login');

        if (!in_array($purpose, ['login', 'register', 'verify'])) {
            return $this->errorResponse(
                message: 'نوع کد تأیید نامعتبر است.',
                statusCode: 400
            );
        }

        $result = $this->otpService->generateAndSend($request->phone, $purpose);

        if ($result['success']) {
            return $this->successResponse(
                data: $result['data'],
                message: $result['message']
            );
        }

        // اگر به دلیل rate limit خطا بود
        if (isset($result['data']['retry_after'])) {
            return $this->rateLimitResponse(
                message: $result['message'],
                retryAfter: $result['data']['retry_after']
            );
        }

        return $this->errorResponse(
            message: $result['message'],
            statusCode: 400
        );
    }
}
