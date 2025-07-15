<?php

namespace App\Actions\User;

use App\Models\User;
use App\Models\Consultant;
use App\Models\Notification;
use App\Enums\UserRole;
use App\Services\MediaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class ConsultantUpgradeAction
{
    public function __construct(
        private MediaService $mediaService
    ) {}

    /**
     * ارسال درخواست ارتقا به مشاور
     */
    public function submitRequest(User $user, array $data, ?UploadedFile $profileImage = null): array
    {
        Log::info('ConsultantUpgrade started', [
            'user_id' => $user->id,
            'data' => $data,
            'has_image' => $profileImage !== null
        ]);

        try {
            return DB::transaction(function () use ($user, $data, $profileImage) {
                // بررسی مجوز
                if (!$user->canRequestConsultantUpgrade()) {
                    return [
                        'success' => false,
                        'message' => 'شما مجاز به ارسال درخواست ارتقا نیستید.',
                        'data' => null
                    ];
                }

                // آپلود تصویر پروفایل (اختیاری)
                $profileImagePath = null;
                if ($profileImage) {
                    $profileImagePath = $this->mediaService->uploadImage(
                        $profileImage,
                        'consultants/profiles'
                    );
                }

                // ایجاد درخواست مشاور
                $consultant = Consultant::create([
                    'user_id' => $user->id,
                    'company_name' => $data['company_name'],
                    'bio' => $data['bio'],
                    'contact_phone' => $data['contact_phone'],
                    'contact_whatsapp' => $data['contact_whatsapp'] ?? null,
                    'contact_telegram' => $data['contact_telegram'] ?? null,
                    'contact_instagram' => $data['contact_instagram'] ?? null,
                    'profile_image' => $profileImagePath,
                    'is_verified' => false, // منتظر تایید ادمین
                ]);

                // ارسال نوتیفیکیشن به ادمین‌ها
                $this->notifyAdmins($user, $consultant);

                Log::info('Consultant upgrade request submitted', [
                    'user_id' => $user->id,
                    'consultant_id' => $consultant->id,
                    'company_name' => $data['company_name']
                ]);

                return [
                    'success' => true,
                    'message' => 'درخواست ارتقا با موفقیت ارسال شد. پس از بررسی ادمین، نتیجه به اطلاع شما خواهد رسید.',
                    'data' => [
                        'consultant_id' => $consultant->id,
                        'status' => 'pending',
                        'submitted_at' => $consultant->created_at->toISOString()
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('Consultant upgrade request failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ارسال درخواست. لطفا دوباره تلاش کنید.',
                'data' => null
            ];
        }
    }

    /**
     * تایید درخواست ارتقا توسط ادمین
     */
    public function approveRequest(Consultant $consultant): array
    {
        try {
            return DB::transaction(function () use ($consultant) {
                // تایید مشاور
                $consultant->update(['is_verified' => true]);

                // ارتقا نقش کاربر
                $consultant->user->update(['user_type' => UserRole::CONSULTANT]);

                // ارسال نوتیف به کاربر
                $this->notifyUserApproval($consultant->user);

                Log::info('Consultant upgrade approved', [
                    'user_id' => $consultant->user_id,
                    'consultant_id' => $consultant->id,
                    'approved_by' => auth()->id()
                ]);

                return [
                    'success' => true,
                    'message' => 'درخواست ارتقا تایید شد.',
                    'data' => [
                        'user_id' => $consultant->user_id,
                        'new_role' => 'consultant',
                        'approved_at' => now()->toISOString()
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('Consultant upgrade approval failed', [
                'consultant_id' => $consultant->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تایید درخواست.',
                'data' => null
            ];
        }
    }

    /**
     * رد درخواست ارتقا توسط ادمین
     */
    public function rejectRequest(Consultant $consultant, string $reason): array
    {
        try {
            return DB::transaction(function () use ($consultant, $reason) {
                // ارسال نوتیف رد به کاربر
                $this->notifyUserRejection($consultant->user, $reason);

                // حذف رکورد مشاور
                $consultant->delete();

                Log::info('Consultant upgrade rejected', [
                    'user_id' => $consultant->user_id,
                    'consultant_id' => $consultant->id,
                    'reason' => $reason,
                    'rejected_by' => auth()->id()
                ]);

                return [
                    'success' => true,
                    'message' => 'درخواست ارتقا رد شد.',
                    'data' => [
                        'user_id' => $consultant->user_id,
                        'rejected_at' => now()->toISOString(),
                        'reason' => $reason
                    ]
                ];
            });
        } catch (\Exception $e) {
            Log::error('Consultant upgrade rejection failed', [
                'consultant_id' => $consultant->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در رد درخواست.',
                'data' => null
            ];
        }
    }

    /**
     * ارسال نوتیفیکیشن به ادمین‌ها
     */
    private function notifyAdmins(User $user, Consultant $consultant): void
    {
        $admins = User::where('user_type', UserRole::ADMIN)->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'sender_id' => $user->id,
                'title' => 'درخواست ارتقا به مشاور',
                'message' => "کاربر {$user->full_name} درخواست ارتقا به مشاور ارسال کرده است.",
                'notification_type' => 'info',
                'target_type' => 'specific_user',
                'priority' => 'high',
                'action_url' => "/admin/consultant-requests/{$consultant->id}",
                'action_text' => 'بررسی درخواست',
            ]);
        }
    }

    /**
     * ارسال نوتیف تایید به کاربر
     */
    private function notifyUserApproval(User $user): void
    {
        Notification::create([
            'user_id' => $user->id,
            'sender_id' => User::where('user_type', UserRole::ADMIN)->first()->id,
            'title' => 'تایید ارتقا به مشاور',
            'message' => 'تبریک! درخواست ارتقا به مشاور شما تایید شد. اکنون می‌توانید از امکانات مشاوره استفاده کنید.',
            'notification_type' => 'success',
            'target_type' => 'specific_user',
            'priority' => 'high',
            'action_url' => '/consultant/dashboard',
            'action_text' => 'ورود به پنل مشاور',
        ]);
    }

    /**
     * ارسال نوتیف رد به کاربر
     */
    private function notifyUserRejection(User $user, string $reason): void
    {
        Notification::create([
            'user_id' => $user->id,
            'sender_id' => User::where('user_type', UserRole::ADMIN)->first()->id,
            'title' => 'رد درخواست ارتقا',
            'message' => "متأسفانه درخواست ارتقا شما رد شد. دلیل: {$reason}",
            'notification_type' => 'warning',
            'target_type' => 'specific_user',
            'priority' => 'normal',
            'action_url' => '/profile',
            'action_text' => 'مشاهده پروفایل',
        ]);
    }
}
