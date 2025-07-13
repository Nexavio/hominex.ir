<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\User\CreateUserAction;
use App\DTOs\User\UserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CreateUserAction $createUserAction
    ) {}

    /**
     * ثبت نام کاربر جدید
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $userData = UserData::fromArray($request->validated());

        $result = $this->createUserAction->execute($userData);

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
     * تأیید کد OTP برای ثبت نام
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $result = $this->createUserAction->verifyPhone(
            $request->phone,
            $request->code
        );

        if ($result['success']) {
            return $this->successResponse(
                data: $result['data'],
                message: $result['message']
            );
        }

        if (isset($result['data']['remaining_attempts'])) {
            return $this->errorResponse(
                message: $result['message'],
                errors: $result['data'],
                statusCode: 400
            );
        }

        return $this->errorResponse(
            message: $result['message'],
            statusCode: 400
        );
    }
}
