<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\OtpService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    use ApiResponse;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private OtpService $otpService
    ) {}

    /**
     * ورود کاربر با پسورد یا درخواست OTP
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->userRepository->findByPhone($request->phone);

        if (!$user) {
            return $this->errorResponse(
                message: 'کاربری با این شماره تماس یافت نشد.',
                statusCode: 404
            );
        }

        if (!$user->is_active) {
            return $this->errorResponse(
                message: 'حساب کاربری شما غیرفعال است.',
                statusCode: 403
            );
        }

        if (!$user->phone_verified_at) {
            return $this->errorResponse(
                message: 'شماره تماس شما هنوز تأیید نشده است.',
                statusCode: 403
            );
        }

        // ورود با پسورد
        if ($request->login_type === 'password') {
            return $this->loginWithPassword($user, $request->password);
        }

        // ورود با OTP
        if ($request->login_type === 'otp') {
            return $this->requestOtpForLogin($user);
        }

        return $this->errorResponse(
            message: 'نوع ورود نامعتبر است.',
            statusCode: 400
        );
    }

    /**
     * تأیید کد OTP برای ورود
     */
    public function verifyLoginOtp(VerifyOtpRequest $request): JsonResponse
    {
        $user = $this->userRepository->findByPhone($request->phone);

        if (!$user) {
            return $this->errorResponse(
                message: 'کاربری با این شماره تماس یافت نشد.',
                statusCode: 404
            );
        }

        $result = $this->otpService->verify($request->phone, $request->code, 'login');

        if (!$result['success']) {
            return $this->errorResponse(
                message: $result['message'],
                errors: $result['data'],
                statusCode: 400
            );
        }

        return $this->generateTokenResponse($user);
    }

    /**
     * خروج کاربر
     */
    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return $this->successResponse(
                message: 'با موفقیت خارج شدید.'
            );
        } catch (\Exception $e) {
            Log::error('Logout failed', ['error' => $e->getMessage()]);

            return $this->errorResponse(
                message: 'خطا در خروج از حساب کاربری.',
                statusCode: 500
            );
        }
    }

    /**
     * تجدید توکن
     */
    public function refresh(): JsonResponse
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());

            return $this->successResponse([
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60
            ], 'توکن با موفقیت تجدید شد.');
        } catch (\Exception $e) {
            Log::error('Token refresh failed', ['error' => $e->getMessage()]);

            return $this->errorResponse(
                message: 'خطا در تجدید توکن.',
                statusCode: 401
            );
        }
    }

    /**
     * دریافت اطلاعات کاربر
     */
    public function me(): JsonResponse
    {
        $user = auth()->user();

        return $this->successResponse([
            'id' => $user->id,
            'phone' => $user->phone,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'user_type' => $user->user_type,
            'phone_verified_at' => $user->phone_verified_at?->toISOString(),
            'created_at' => $user->created_at->toISOString()
        ]);
    }

    /**
     * ورود با پسورد
     */
    private function loginWithPassword($user, string $password): JsonResponse
    {
        if (!Hash::check($password, $user->password)) {
            return $this->errorResponse(
                message: 'رمز عبور اشتباه است.',
                statusCode: 401
            );
        }

        return $this->generateTokenResponse($user);
    }

    /**
     * درخواست OTP برای ورود
     */
    private function requestOtpForLogin($user): JsonResponse
    {
        $result = $this->otpService->generateAndSend($user->phone, 'login');

        if ($result['success']) {
            return $this->successResponse(
                data: $result['data'],
                message: $result['message']
            );
        }

        return $this->errorResponse(
            message: $result['message'],
            errors: $result['data'],
            statusCode: 400
        );
    }

    /**
     * تولید پاسخ توکن
     */
    private function generateTokenResponse($user): JsonResponse
    {
        try {
            $token = JWTAuth::fromUser($user);

            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'phone' => $user->phone
            ]);

            return $this->successResponse([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
                'user' => [
                    'id' => $user->id,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'full_name' => $user->full_name,
                    'user_type' => $user->user_type
                ]
            ], 'ورود با موفقیت انجام شد.');
        } catch (\Exception $e) {
            Log::error('Token generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return $this->serverErrorResponse(
                'خطا در تولید توکن دسترسی.'
            );
        }
    }
}
