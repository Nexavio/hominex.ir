<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsultationRequest extends Model
{
    protected $fillable = [
        'user_id',
        'consultant_id',
        'property_id',
        'full_name',
        'phone',
        'message',
        'preferred_contact_method',
        'preferred_contact_time',
        'status',
        'consultant_notes',
    ];

    protected function casts(): array
    {
        return [
            'preferred_contact_method' => 'string',
            'status' => 'string',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeContacted($query)
    {
        return $query->where('status', 'contacted');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeForConsultant($query, int $consultantId)
    {
        return $query->where('consultant_id', $consultantId);
    }

    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'در انتظار',
            'contacted' => 'تماس گرفته شده',
            'in_progress' => 'در حال پیگیری',
            'completed' => 'تکمیل شده',
            'cancelled' => 'لغو شده',
            default => 'نامشخص',
        };
    }

    public function getContactMethodLabelAttribute(): string
    {
        return match ($this->preferred_contact_method) {
            'phone' => 'تماس تلفنی',
            'whatsapp' => 'واتساپ',
            'telegram' => 'تلگرام',
            default => 'تماس تلفنی',
        };
    }

    public function getFormattedTimeAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getIsOpenAttribute(): bool
    {
        return in_array($this->status, ['pending', 'contacted', 'in_progress']);
    }

    public function getIsClosedAttribute(): bool
    {
        return in_array($this->status, ['completed', 'cancelled']);
    }

    // Methods
    public function markAsContacted(string $notes = null): bool
    {
        $data = ['status' => 'contacted'];

        if ($notes) {
            $data['consultant_notes'] = $notes;
        }

        return $this->update($data);
    }

    public function markAsInProgress(string $notes = null): bool
    {
        $data = ['status' => 'in_progress'];

        if ($notes) {
            $data['consultant_notes'] = $notes;
        }

        return $this->update($data);
    }

    public function markAsCompleted(string $notes = null): bool
    {
        $data = ['status' => 'completed'];

        if ($notes) {
            $data['consultant_notes'] = $notes;
        }

        return $this->update($data);
    }

    public function cancel(string $notes = null): bool
    {
        $data = ['status' => 'cancelled'];

        if ($notes) {
            $data['consultant_notes'] = $notes;
        }

        return $this->update($data);
    }

    public function canBeUpdatedBy(User $user): bool
    {
        // فقط مشاور مربوطه یا ادمین می‌تواند درخواست را به‌روزرسانی کند
        return $user->user_type === \App\Enums\UserRole::ADMIN ||
               ($this->consultant && $this->consultant->user_id === $user->id);
    }

    public function canBeViewedBy(User $user): bool
    {
        // کاربر درخواست‌دهنده، مشاور مربوطه یا ادمین می‌تواند ببیند
        return $user->user_type === \App\Enums\UserRole::ADMIN ||
               $this->user_id === $user->id ||
               ($this->consultant && $this->consultant->user_id === $user->id);
    }
}
