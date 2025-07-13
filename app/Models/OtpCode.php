<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OtpCode extends Model
{
    protected $fillable = [
        'phone',
        'code',
        'purpose',
        'attempts',
        'expires_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'phone', 'phone');
    }

    // Scopes
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', Carbon::now())
                    ->whereNull('verified_at');
    }

    public function scopeByPhone($query, string $phone)
    {
        return $query->where('phone', $phone);
    }

    public function scopeByPurpose($query, string $purpose)
    {
        return $query->where('purpose', $purpose);
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at < Carbon::now();
    }

    public function getIsVerifiedAttribute(): bool
    {
        return !is_null($this->verified_at);
    }

    public function getIsValidAttribute(): bool
    {
        return !$this->is_expired && !$this->is_verified;
    }

    public function getRemainingAttemptsAttribute(): int
    {
        return max(0, 3 - $this->attempts);
    }
}
