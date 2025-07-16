<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComparisonItem extends Model
{
    protected $fillable = [
        'session_id',
        'property_id',
        'display_order',
        'added_at',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'added_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->added_at) {
                $model->added_at = now();
            }
        });
    }

    // Relationships
    public function session(): BelongsTo
    {
        return $this->belongsTo(ComparisonSession::class, 'session_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    // Scopes
    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    public function scopeByProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    // Accessors
    public function getFormattedAddedAtAttribute(): string
    {
        return $this->added_at->diffForHumans();
    }

    // Methods
    public function moveUp(): bool
    {
        if ($this->display_order <= 1) {
            return false;
        }

        $previousItem = static::where('session_id', $this->session_id)
            ->where('display_order', $this->display_order - 1)
            ->first();

        if ($previousItem) {
            $this->update(['display_order' => $this->display_order - 1]);
            $previousItem->update(['display_order' => $previousItem->display_order + 1]);
            return true;
        }

        return false;
    }

    public function moveDown(): bool
    {
        $maxOrder = static::where('session_id', $this->session_id)->max('display_order');

        if ($this->display_order >= $maxOrder) {
            return false;
        }

        $nextItem = static::where('session_id', $this->session_id)
            ->where('display_order', $this->display_order + 1)
            ->first();

        if ($nextItem) {
            $this->update(['display_order' => $this->display_order + 1]);
            $nextItem->update(['display_order' => $nextItem->display_order - 1]);
            return true;
        }

        return false;
    }

    public function updateOrder(int $newOrder): bool
    {
        if ($newOrder < 1) {
            return false;
        }

        $maxOrder = static::where('session_id', $this->session_id)->max('display_order');

        if ($newOrder > $maxOrder) {
            $newOrder = $maxOrder;
        }

        if ($this->display_order === $newOrder) {
            return true;
        }

        // اگر order جدید کمتر از فعلی است
        if ($newOrder < $this->display_order) {
            static::where('session_id', $this->session_id)
                ->whereBetween('display_order', [$newOrder, $this->display_order - 1])
                ->increment('display_order');
        } else {
            // اگر order جدید بیشتر از فعلی است
            static::where('session_id', $this->session_id)
                ->whereBetween('display_order', [$this->display_order + 1, $newOrder])
                ->decrement('display_order');
        }

        return $this->update(['display_order' => $newOrder]);
    }
}
