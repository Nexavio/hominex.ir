<?php
// app/Models/ComparisonItem.php - نسخه بدون timestamps

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComparisonItem extends Model
{
    // غیرفعال کردن timestamps تا migration اجرا شود
    public $timestamps = false;

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
    public function updateOrder(int $newOrder): bool
    {
        if ($newOrder < 1) {
            return false;
        }

        return $this->update(['display_order' => $newOrder]);
    }
}
