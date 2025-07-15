<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Enums\UserRole;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * لیست همه نوتیفیکیشن‌ها
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notification::with(['user', 'sender'])
            ->orderBy('created_at', 'desc');

        // فیلتر بر اساس نوع
        if ($request->has('type')) {
            $query->where('notification_type', $request->type);
        }

        // فیلتر بر اساس وضعیت خوانده شده
        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        // فیلتر بر اساس کاربر
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $notifications = $query->paginate(20);

        return $this->successResponse([
            'notifications' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'total_pages' => $notifications->lastPage(),
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
            ]
        ]);
    }

    /**
     * ارسال نوتیفیکیشن عمومی
     */
    public function sendBroadcast(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'notification_type' => 'required|in:info,warning,success,error,announcement',
            'target_type' => 'required|in:all_users,consultants,regular_users',
            'priority' => 'required|in:low,normal,high,urgent',
            'action_url' => 'nullable|string|max:500',
            'action_text' => 'nullable|string|max:100',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $sender = auth()->user();
        $users = $this->getTargetUsers($request->target_type);

        $notifications = [];
        foreach ($users as $user) {
            $notifications[] = [
                'user_id' => $user->id,
                'sender_id' => $sender->id,
                'title' => $request->title,
                'message' => $request->message,
                'notification_type' => $request->notification_type,
                'target_type' => $request->target_type,
                'priority' => $request->priority,
                'action_url' => $request->action_url,
                'action_text' => $request->action_text,
                'expires_at' => $request->expires_at,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Notification::insert($notifications);

        return $this->successResponse([
            'sent_count' => count($notifications),
            'target_type' => $request->target_type,
        ], 'نوتیفیکیشن با موفقیت ارسال شد.');
    }

    /**
     * آمار نوتیفیکیشن‌ها
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Notification::count(),
            'unread' => Notification::where('is_read', false)->count(),
            'by_type' => Notification::selectRaw('notification_type, COUNT(*) as count')
                ->groupBy('notification_type')
                ->pluck('count', 'notification_type'),
            'recent_24h' => Notification::where('created_at', '>=', now()->subDay())->count(),
        ];

        return $this->successResponse($stats);
    }

    private function getTargetUsers(string $targetType): \Illuminate\Database\Eloquent\Collection
    {
        return match ($targetType) {
            'all_users' => User::active()->get(),
            'consultants' => User::where('user_type', UserRole::CONSULTANT)->active()->get(),
            'regular_users' => User::where('user_type', UserRole::REGULAR)->active()->get(),
            default => collect(),
        };
    }
}
