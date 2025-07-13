<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consultant extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'profile_image',
        'bio',
        'contact_phone',
        'contact_whatsapp',
        'contact_telegram',
        'contact_instagram',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function consultationRequests(): HasMany
    {
        return $this->hasMany(ConsultationRequest::class);
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeActive($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->where('is_active', true);
        });
    }

    public function scopeWithActiveProperties($query)
    {
        return $query->whereHas('properties', function ($q) {
            $q->where('status', 'approved');
        });
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return $this->user->full_name ?? 'نامشخص';
    }

    public function getPhoneAttribute(): string
    {
        return $this->user->phone ?? '';
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user->email;
    }

    public function getPropertiesCountAttribute(): int
    {
        return $this->properties()->where('status', 'approved')->count();
    }

    public function getActivePropertiesCountAttribute(): int
    {
        return $this->properties()
                   ->where('status', 'approved')
                   ->whereNotNull('published_at')
                   ->count();
    }

    public function getProfileImageUrlAttribute(): ?string
    {
        if ($this->profile_image) {
            return asset('storage/' . $this->profile_image);
        }

        return null;
    }

    // Methods
    public function verify(): bool
    {
        return $this->update(['is_verified' => true]);
    }

    public function unverify(): bool
    {
        return $this->update(['is_verified' => false]);
    }

    public function hasContactMethod(string $method): bool
    {
        return match ($method) {
            'phone' => !empty($this->contact_phone),
            'whatsapp' => !empty($this->contact_whatsapp),
            'telegram' => !empty($this->contact_telegram),
            'instagram' => !empty($this->contact_instagram),
            default => false,
        };
    }

    public function getContactInfo(): array
    {
        return [
            'phone' => $this->contact_phone,
            'whatsapp' => $this->contact_whatsapp,
            'telegram' => $this->contact_telegram,
            'instagram' => $this->contact_instagram,
        ];
    }
}
