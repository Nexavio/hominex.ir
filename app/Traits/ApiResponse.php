<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    /**
     * پاسخ موفقیت‌آمیز
     */
    protected function successResponse(
        $data = null,
        string $message = 'عملیات با موفقیت انجام شد.',
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ], $statusCode);
    }

    /**
     * پاسخ خطا
     */
    protected function errorResponse(
        string $message = 'خطایی رخ داده است.',
        $errors = null,
        int $statusCode = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString()
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * پاسخ اعتبارسنجی
     */
    protected function validationErrorResponse(
        $errors,
        string $message = 'داده‌های ورودی نامعتبر است.'
    ): JsonResponse {
        return $this->errorResponse(
            message: $message,
            errors: $errors,
            statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }

    /**
     * پاسخ عدم دسترسی
     */
    protected function unauthorizedResponse(
        string $message = 'شما مجاز به انجام این عملیات نیستید.'
    ): JsonResponse {
        return $this->errorResponse(
            message: $message,
            statusCode: Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * پاسخ یافت نشد
     */
    protected function notFoundResponse(
        string $message = 'منبع مورد نظر یافت نشد.'
    ): JsonResponse {
        return $this->errorResponse(
            message: $message,
            statusCode: Response::HTTP_NOT_FOUND
        );
    }

    /**
     * پاسخ محدودیت نرخ
     */
    protected function rateLimitResponse(
        string $message = 'تعداد درخواست‌های شما بیش از حد مجاز است.',
        int $retryAfter = null
    ): JsonResponse {
        $response = $this->errorResponse(
            message: $message,
            statusCode: Response::HTTP_TOO_MANY_REQUESTS
        );

        if ($retryAfter) {
            $response->header('Retry-After', $retryAfter);
        }

        return $response;
    }

    /**
     * پاسخ خطای سرور
     */
    protected function serverErrorResponse(
        string $message = 'خطای داخلی سرور رخ داده است.'
    ): JsonResponse {
        return $this->errorResponse(
            message: $message,
            statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
