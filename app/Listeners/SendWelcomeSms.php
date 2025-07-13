<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendWelcomeSms implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private SmsService $smsService
    ) {}

    public function handle(UserRegistered $event): void
    {
        // ارسال پیام خوش‌آمدگویی بعد از تأیید شماره تماس
        if ($event->user->phone_verified_at) {
            $sent = $this->smsService->sendWelcomeMessage(
                $event->user->phone,
                $event->user->full_name ?? 'کاربر گرامی'
            );

            if ($sent) {
                Log::info('Welcome SMS sent successfully', [
                    'user_id' => $event->user->id,
                    'phone' => $event->user->phone
                ]);
            }
        }
    }

    public function failed(UserRegistered $event, \Throwable $exception): void
    {
        Log::error('Failed to send welcome SMS', [
            'user_id' => $event->user->id,
            'phone' => $event->user->phone,
            'error' => $exception->getMessage()
        ]);
    }
}
