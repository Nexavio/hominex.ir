<?php
// app/Models/User.php
namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'phone',
        'email',
        'full_name',
        'password',
        'user_type',
        'is_active',
        'phone_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'user_type' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Relationships
    public function consultant()
    {
        return $this->hasOne(Consultant::class);
    }

    public function otpCodes()
    {
        return $this->hasMany(OtpCode::class, 'phone', 'phone');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function consultationRequests()
    {
        return $this->hasMany(ConsultationRequest::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    // رابطه جدید: آگهی‌هایی که این کاربر ثبت کرده
    public function createdProperties()
    {
        return $this->hasMany(Property::class, 'created_by_user_id');
    }

    // رابطه جدید: اعلانات دریافتی
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // رابطه جدید: اعلانات ارسالی (برای ادمین)
    public function sentNotifications()
    {
        return $this->hasMany(Notification::class, 'sender_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('phone_verified_at');
    }

    public function scopeByUserType($query, UserRole $userType)
    {
        return $query->where('user_type', $userType);
    }

    // Scope جدید: کاربرانی که درخواست مشاور دادن
    public function scopeWithPendingConsultantRequest($query)
    {
        return $query->whereHas('consultant', function ($q) {
            $q->where('is_verified', false);
        });
    }

    // Accessors & Mutators
    public function getIsPhoneVerifiedAttribute(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    public function getIsConsultantAttribute(): bool
    {
        return $this->user_type === UserRole::CONSULTANT;
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->user_type === UserRole::ADMIN;
    }

    // Accessor جدید: آیا درخواست مشاور در انتظار تایید دارد؟
    public function getHasPendingConsultantRequestAttribute(): bool
    {
        return $this->consultant && !$this->consultant->is_verified && $this->user_type === UserRole::REGULAR;
    }

    // Accessor جدید: تعداد آگهی‌های ثبت شده
    public function getCreatedPropertiesCountAttribute(): int
    {
        return $this->createdProperties()->count();
    }

    // Accessor جدید: تعداد آگهی‌های تایید شده
    public function getApprovedPropertiesCountAttribute(): int
    {
        return $this->createdProperties()->where('status', 'approved')->count();
    }

    // Accessor جدید: تعداد آگهی‌های در انتظار
    public function getPendingPropertiesCountAttribute(): int
    {
        return $this->createdProperties()->where('status', 'pending')->count();
    }

    // Accessor جدید: تعداد اعلانات خوانده نشده
    public function getUnreadNotificationsCountAttribute(): int
    {
        return $this->notifications()->where('is_read', false)->count();
    }

    // Methods جدید
    public function canCreateProperty(): bool
    {
        // بررسی فعال بودن حساب
        if (!$this->is_active) {
            return false;
        }

        // بررسی تایید شماره تماس
        if (!$this->phone_verified_at) {
            return false;
        }

        // ادمین و مشاور همیشه می‌تونن آگهی ثبت کنن
        if (in_array($this->user_type, [UserRole::ADMIN, UserRole::CONSULTANT])) {
            return true;
        }

        // کاربر معمولی هم می‌تونه آگهی ثبت کنه
        return $this->user_type === UserRole::REGULAR;
    }

    public function canRequestConsultantUpgrade(): bool
    {
        // فقط کاربر معمولی می‌تونه درخواست ارتقا بده
        if ($this->user_type !== UserRole::REGULAR) {
            return false;
        }

        // حساب باید فعال باشه
        if (!$this->is_active || !$this->phone_verified_at) {
            return false;
        }

        // نباید قبلاً درخواست داده باشه یا درخواستش رد شده باشه
        return !$this->consultant;
    }

    public function upgradeToConsultant(): bool
    {
        if ($this->user_type !== UserRole::REGULAR) {
            return false;
        }

        if (!$this->consultant || !$this->consultant->is_verified) {
            return false;
        }

        return $this->update(['user_type' => UserRole::CONSULTANT]);
    }

    public function hasUnreadMessages(): bool
    {
        return $this->receivedMessages()->where('is_read', false)->exists();
    }

    public function getUnreadMessagesCount(): int
    {
        return $this->receivedMessages()->where('is_read', false)->count();
    }

    public function markAllNotificationsAsRead(): int
    {
        return $this->notifications()
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
    }
}
