<?php

namespace App\Actions\User;

use App\DTOs\User\UserData;
use App\Events\UserRegistered;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\OtpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateUserAction
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private OtpService $otpService
    ) {}

    /**
     * ثبت نام کاربر جدید
     */
    public function execute(UserData $userData): array
    {
        try {
            return DB::transaction(function () use ($userData) {
                // بررسی وجود کاربر قبلی
                if ($this->userRepository->exists($userData->phone, $userData->email)) {
                    return [
                        'success' => false,
                        'message' => 'کاربری با این شماره تماس یا ایمیل قبلاً ثبت شده است.',
                        'data' => null
                    ];
                }

                // ایجاد کاربر
                $user = $this->userRepository->create($userData);

                // ارسال کد تأیید
                $otpResult = $this->otpService->generateAndSend($userData->phone, 'register');

                if (!$otpResult['success']) {
                    throw new \Exception('خطا در ارسال کد تأیید: ' . $otpResult['message']);
                }

                // فراخوانی ایونت ثبت نام
                event(new UserRegistered($user));

                Log::info('User registered successfully', [
                    'user_id' => $user->id,
                    'phone' => $user->phone
                ]);

                return [
                    'success' => true,
                    'message' => 'ثبت نام با موفقیت انجام شد. کد تأیید برای شما ارسال گردید.',
                    'data' => [
                        'user_id' => $user->id,
                        'phone' => $user->phone,
                        'otp_expires_at' => $otpResult['data']['expires_at']
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('User registration failed', [
                'phone' => $userData->phone,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ثبت نام. لطفا دوباره تلاش کنید.',
                'data' => null
            ];
        }
    }

    /**
     * تأیید شماره تماس کاربر
     */
    public function verifyPhone(string $phone, string $code): array
    {
        try {
            return DB::transaction(function () use ($phone, $code) {
                // تأیید کد OTP
                $verifyResult = $this->otpService->verify($phone, $code, 'register');

                if (!$verifyResult['success']) {
                    return $verifyResult;
                }

                // یافتن کاربر
                $user = $this->userRepository->findByPhone($phone);

                if (!$user) {
                    return [
                        'success' => false,
                        'message' => 'کاربر یافت نشد.',
                        'data' => null
                    ];
                }

                // تأیید شماره تماس
                $user = $this->userRepository->verifyPhone($user);

                Log::info('Phone verified successfully', [
                    'user_id' => $user->id,
                    'phone' => $user->phone
                ]);

                return [
                    'success' => true,
                    'message' => 'شماره تماس با موفقیت تأیید شد.',
                    'data' => [
                        'user_id' => $user->id,
                        'phone_verified' => true,
                        'verified_at' => $user->phone_verified_at->toISOString()
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('Phone verification failed', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تأیید شماره تماس.',
                'data' => null
            ];
        }
    }
}
