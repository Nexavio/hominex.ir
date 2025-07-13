<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    protected $fillable = [
        'user_id',
        'property_id',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    // Scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeWithActiveProperties($query)
    {
        return $query->whereHas('property', function ($q) {
            $q->where('status', 'approved')
                ->whereNotNull('published_at');
        });
    }

    // Methods
    public static function toggle(int $userId, int $propertyId): bool
    {
        $favorite = static::where('user_id', $userId)
            ->where('property_id', $propertyId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return false; // removed
        } else {
            static::create([
                'user_id' => $userId,
                'property_id' => $propertyId,
            ]);
            return true; // added
        }
    }

    public static function isFavorited(int $userId, int $propertyId): bool
    {
        return static::where('user_id', $userId)
            ->where('property_id', $propertyId)
            ->exists();
    }

    public static function getUserFavoriteIds(int $userId): array
    {
        return static::where('user_id', $userId)
            ->pluck('property_id')
            ->toArray();
    }

    public static function getPopularPropertyIds(int $limit = 10): array
    {
        return static::select('property_id')
            ->selectRaw('COUNT(*) as favorites_count')
            ->groupBy('property_id')
            ->orderByDesc('favorites_count')
            ->limit($limit)
            ->pluck('property_id')
            ->toArray();
    }
}
