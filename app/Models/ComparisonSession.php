<?php
// app/Models/ComparisonSession.php - نسخه بدون timestamps

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComparisonSession extends Model
{
    // غیرفعال کردن timestamps تا migration اجرا شود
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'device_fingerprint',
        'is_active',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ComparisonItem::class, 'session_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByFingerprint($query, string $fingerprint)
    {
        return $query->where('device_fingerprint', $fingerprint);
    }

    // Methods
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    public function getItemsCount(): int
    {
        return $this->items()->count();
    }

    public function canAddMoreItems(int $maxItems = 4): bool
    {
        return $this->getItemsCount() < $maxItems;
    }

    public function addProperty(int $propertyId): ?ComparisonItem
    {
        // بررسی اینکه قبلاً اضافه نشده باشد
        if ($this->items()->where('property_id', $propertyId)->exists()) {
            return null;
        }

        $nextOrder = $this->items()->max('display_order') + 1;

        return $this->items()->create([
            'property_id' => $propertyId,
            'display_order' => $nextOrder,
        ]);
    }

    public function removeProperty(int $propertyId): bool
    {
        $item = $this->items()->where('property_id', $propertyId)->first();

        if ($item) {
            $item->delete();
            $this->reorderItems();
            return true;
        }

        return false;
    }

    public function clearItems(): void
    {
        $this->items()->delete();
    }

    private function reorderItems(): void
    {
        $items = $this->items()->orderBy('display_order')->get();

        foreach ($items as $index => $item) {
            $item->update(['display_order' => $index + 1]);
        }
    }
}
