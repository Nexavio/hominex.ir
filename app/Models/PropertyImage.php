<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyImage extends Model
{
    protected $fillable = [
        'property_id',
        'image_url',
        'thumbnail_url',
        'is_primary',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    // Relationships
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    // Accessors
    public function getFullImageUrlAttribute(): string
    {
        if (str_starts_with($this->image_url, 'http')) {
            return $this->image_url;
        }

        return asset('storage/' . $this->image_url);
    }

    public function getFullThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail_url) {
            return $this->full_image_url;
        }

        if (str_starts_with($this->thumbnail_url, 'http')) {
            return $this->thumbnail_url;
        }

        return asset('storage/' . $this->thumbnail_url);
    }

    // Methods
    public function makePrimary(): bool
    {
        // ابتدا تصویر اصلی فعلی را غیرفعال می‌کنیم
        $this->property->images()->update(['is_primary' => false]);

        // سپس این تصویر را اصلی می‌کنیم
        return $this->update(['is_primary' => true]);
    }

    public function updateOrder(int $order): bool
    {
        return $this->update(['display_order' => $order]);
    }

    public static function reorderForProperty(int $propertyId, array $imageIds): void
    {
        foreach ($imageIds as $order => $imageId) {
            static::where('id', $imageId)
                ->where('property_id', $propertyId)
                ->update(['display_order' => $order + 1]);
        }
    }
}
