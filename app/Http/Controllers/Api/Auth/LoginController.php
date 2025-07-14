<?php
// app/Http/Controllers/Api/Auth/LoginController.php
namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\OtpService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class LoginController extends Controller
{
    use ApiResponse;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private OtpService $otpService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            Log::info('Login attempt', ['phone' => $request->phone, 'type' => $request->login_type]);

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
                if (!$request->password) {
                    return $this->errorResponse(
                        message: 'رمز عبور الزامی است.',
                        statusCode: 400
                    );
                }
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
        } catch (\Exception $e) {
            Log::error('Login failed', [
                'phone' => $request->phone ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->serverErrorResponse(
                'خطا در ورود. لطفا دوباره تلاش کنید.'
            );
        }
    }

    public function verifyLoginOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
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
                    errors: $result['data'] ?? null,
                    statusCode: 400
                );
            }

            return $this->generateTokenResponse($user);
        } catch (\Exception $e) {
            Log::error('OTP verification failed', [
                'phone' => $request->phone,
                'error' => $e->getMessage()
            ]);

            return $this->serverErrorResponse(
                'خطا در تأیید کد. لطفا دوباره تلاش کنید.'
            );
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $token = JWTAuth::getToken();
            if ($token) {
                JWTAuth::invalidate($token);
            }

            return $this->successResponse(
                message: 'با موفقیت خارج شدید.'
            );
        } catch (JWTException $e) {
            Log::error('Logout failed', ['error' => $e->getMessage()]);

            return $this->errorResponse(
                message: 'خطا در خروج از حساب کاربری.',
                statusCode: 500
            );
        }
    }

    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::getToken();
            $newToken = JWTAuth::refresh($token);

            return $this->successResponse([
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60
            ], 'توکن با موفقیت تجدید شد.');
        } catch (JWTException $e) {
            Log::error('Token refresh failed', ['error' => $e->getMessage()]);

            return $this->errorResponse(
                message: 'خطا در تجدید توکن.',
                statusCode: 401
            );
        }
    }

    public function me(): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return $this->errorResponse(
                    message: 'کاربر احراز هویت نشده.',
                    statusCode: 401
                );
            }

            return $this->successResponse([
                'id' => $user->id,
                'phone' => $user->phone,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'user_type' => $user->user_type->value,
                'phone_verified_at' => $user->phone_verified_at?->toISOString(),
                'created_at' => $user->created_at->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Get user profile failed', ['error' => $e->getMessage()]);

            return $this->serverErrorResponse(
                'خطا در دریافت اطلاعات کاربر.'
            );
        }
    }

    private function loginWithPassword($user, string $password): JsonResponse
    {
        if (!Hash::check($password, $user->password)) {
            Log::warning('Wrong password attempt', ['user_id' => $user->id]);
            return $this->errorResponse(
                message: 'رمز عبور اشتباه است.',
                statusCode: 401
            );
        }

        return $this->generateTokenResponse($user);
    }

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
            errors: $result['data'] ?? null,
            statusCode: 400
        );
    }

    private function generateTokenResponse($user): JsonResponse
    {
        try {
            Log::info('Generating token for user', ['user_id' => $user->id]);

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
                    'user_type' => $user->user_type->value
                ]
            ], 'ورود با موفقیت انجام شد.');
        } catch (JWTException $e) {
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
