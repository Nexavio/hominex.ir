<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Property extends Model
{
    protected $fillable = [
        'consultant_id',
        'created_by_user_id', // فیلد جدید اضافه شد
        'property_type_id',
        'title',
        'description',
        'property_status',
        'total_price',
        'rent_deposit',
        'monthly_rent',
        'land_area',
        'building_year',
        'rooms_count',
        'bathrooms_count',
        'document_type',
        'total_units',
        'usage_type',
        'direction',
        'latitude',
        'longitude',
        'province',
        'city',
        'address',
        'features',
        'status',
        'rejection_reason',
        'views_count',
        'is_featured',
        'featured_until',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:0',
            'rent_deposit' => 'decimal:0',
            'monthly_rent' => 'decimal:0',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'features' => 'array',
            'is_featured' => 'boolean',
            'featured_until' => 'datetime',
            'published_at' => 'datetime',
            'views_count' => 'integer',
            'land_area' => 'integer',
            'building_year' => 'integer',
            'rooms_count' => 'integer',
            'bathrooms_count' => 'integer',
            'total_units' => 'integer',
        ];
    }

    // Relationships
    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    // رابطه جدید: کاربری که آگهی را ثبت کرده
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(PropertyAmenity::class, 'property_has_amenities', 'property_id', 'amenity_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function consultationRequests(): HasMany
    {
        return $this->hasMany(ConsultationRequest::class);
    }

    // Scopes موجود
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'approved')
                    ->whereNotNull('published_at');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
                    ->where(function ($q) {
                        $q->whereNull('featured_until')
                          ->orWhere('featured_until', '>', now());
                    });
    }

    public function scopeForSale($query)
    {
        return $query->where('property_status', 'for_sale');
    }

    public function scopeForRent($query)
    {
        return $query->where('property_status', 'for_rent');
    }

    public function scopeInCity($query, string $city)
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    public function scopeInProvince($query, string $province)
    {
        return $query->where('province', 'like', "%{$province}%");
    }

    public function scopePriceBetween($query, int $min, int $max)
    {
        return $query->where(function ($q) use ($min, $max) {
            $q->whereBetween('total_price', [$min, $max])
              ->orWhereBetween('monthly_rent', [$min, $max]);
        });
    }

    public function scopeWithRooms($query, int $rooms)
    {
        return $query->where('rooms_count', $rooms);
    }

    // Scope جدید: آگهی‌های ثبت شده توسط کاربر خاص
    public function scopeCreatedByUser($query, int $userId)
    {
        return $query->where('created_by_user_id', $userId);
    }

    // Scope جدید: آگهی‌های کاربران معمولی (consultant_id = 1)
    public function scopeByRegularUsers($query)
    {
        return $query->where('consultant_id', 1);
    }

    // Scope جدید: آگهی‌های مشاورین واقعی
    public function scopeByRealConsultants($query)
    {
        return $query->where('consultant_id', '>', 1);
    }

    // Accessors موجود
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'پیش‌نویس',
            'pending' => 'در انتظار تأیید',
            'approved' => 'تأیید شده',
            'rejected' => 'رد شده',
            'archived' => 'آرشیو شده',
            default => 'نامشخص',
        };
    }

    public function getPropertyStatusLabelAttribute(): string
    {
        return match ($this->property_status) {
            'for_sale' => 'فروش',
            'for_rent' => 'اجاره',
            default => 'نامشخص',
        };
    }

    public function getFormattedPriceAttribute(): string
    {
        if ($this->property_status === 'for_sale' && $this->total_price) {
            return number_format($this->total_price) . ' تومان';
        }

        if ($this->property_status === 'for_rent' && $this->monthly_rent) {
            return number_format($this->monthly_rent) . ' تومان/ماه';
        }

        return 'توافقی';
    }

    public function getPrimaryImageAttribute(): ?PropertyImage
    {
        return $this->images()->where('is_primary', true)->first()
               ?? $this->images()->orderBy('display_order')->first();
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        return $this->primary_image?->image_url;
    }

    public function getIsFavoritedAttribute(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return $this->favorites()->where('user_id', auth()->id())->exists();
    }

    public function getFavoritesCountAttribute(): int
    {
        return $this->favorites()->count();
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'approved' && !is_null($this->published_at);
    }

    public function getIsFeaturedActiveAttribute(): bool
    {
        return $this->is_featured &&
               (is_null($this->featured_until) || $this->featured_until > now());
    }

    // Accessor جدید: آیا این آگهی توسط کاربر معمولی ثبت شده؟
    public function getIsCreatedByRegularUserAttribute(): bool
    {
        return $this->consultant_id === 1;
    }

    // Accessor جدید: نام نمایشی کسی که آگهی را ثبت کرده
    public function getCreatorDisplayNameAttribute(): string
    {
        if ($this->is_created_by_regular_user) {
            return $this->createdByUser?->full_name ?? 'کاربر هومینکس';
        }

        return $this->consultant?->getFullNameAttribute() ?? 'نامشخص';
    }

    // Methods موجود
    public function approve(): bool
    {
        return $this->update([
            'status' => 'approved',
            'published_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    public function reject(string $reason): bool
    {
        return $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'published_at' => null,
        ]);
    }

    public function archive(): bool
    {
        return $this->update(['status' => 'archived']);
    }

    public function feature(int $days = null): bool
    {
        $data = ['is_featured' => true];

        if ($days) {
            $data['featured_until'] = now()->addDays($days);
        }

        return $this->update($data);
    }

    public function unfeature(): bool
    {
        return $this->update([
            'is_featured' => false,
            'featured_until' => null,
        ]);
    }

    public function incrementViews(): bool
    {
        return $this->increment('views_count');
    }

    // Methods جدید
    public function canBeEditedBy(User $user): bool
    {
        // ادمین همه آگهی‌ها رو می‌تونه ویرایش کنه
        if ($user->user_type === \App\Enums\UserRole::ADMIN) {
            return true;
        }

        // مشاور فقط آگهی‌های خودش رو می‌تونه ویرایش کنه
        if ($user->user_type === \App\Enums\UserRole::CONSULTANT) {
            return $this->consultant && $this->consultant->user_id === $user->id;
        }

        // کاربر معمولی فقط آگهی‌هایی که خودش ثبت کرده رو می‌تونه ویرایش کنه
        return $this->created_by_user_id === $user->id;
    }

    public function canBeDeletedBy(User $user): bool
    {
        // ادمین همه آگهی‌ها رو می‌تونه حذف (آرشیو) کنه
        if ($user->user_type === \App\Enums\UserRole::ADMIN) {
            return true;
        }

        // مشاور آگهی‌های خودش رو می‌تونه حذف کنه (اگر در وضعیت draft باشه)
        if ($user->user_type === \App\Enums\UserRole::CONSULTANT) {
            return $this->consultant &&
                   $this->consultant->user_id === $user->id &&
                   in_array($this->status, ['draft', 'pending']);
        }

        // کاربر معمولی آگهی‌های خودش رو می‌تونه حذف کنه
        return $this->created_by_user_id === $user->id &&
               in_array($this->status, ['draft', 'pending']);
    }

    public function canBeViewedBy(User $user = null): bool
    {
        // اگر آگهی منتشر شده، همه می‌تونن ببینن
        if ($this->status === 'approved' && $this->published_at) {
            return true;
        }

        // اگر کاربر لاگین نکرده، فقط آگهی‌های منتشر شده
        if (!$user) {
            return false;
        }

        // ادمین همه آگهی‌ها رو می‌تونه ببینه
        if ($user->user_type === \App\Enums\UserRole::ADMIN) {
            return true;
        }

        // مشاور آگهی‌های خودش رو می‌تونه ببینه
        if ($user->user_type === \App\Enums\UserRole::CONSULTANT) {
            return $this->consultant && $this->consultant->user_id === $user->id;
        }

        // کاربر معمولی آگهی‌های خودش رو می‌تونه ببینه
        return $this->created_by_user_id === $user->id;
    }
}
