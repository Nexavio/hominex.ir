<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'property_id',
        'consultation_request_id',
        'message',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    // Relationships
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function consultationRequest(): BelongsTo
    {
        return $this->belongsTo(ConsultationRequest::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeBetweenUsers($query, int $user1Id, int $user2Id)
    {
        return $query->where(function ($q) use ($user1Id, $user2Id) {
            $q->where('sender_id', $user1Id)->where('receiver_id', $user2Id);
        })->orWhere(function ($q) use ($user1Id, $user2Id) {
            $q->where('sender_id', $user2Id)->where('receiver_id', $user1Id);
        });
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Accessors & Mutators
    public function getIsFromCurrentUserAttribute(): bool
    {
        return $this->sender_id === auth()->id();
    }

    public function getIsToCurrentUserAttribute(): bool
    {
        return $this->receiver_id === auth()->id();
    }

    public function getFormattedTimeAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    // Methods
    public function markAsRead(): bool
    {
        if (!$this->is_read && $this->receiver_id === auth()->id()) {
            return $this->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        }

        return false;
    }

    public function canBeReadBy(User $user): bool
    {
        return $this->sender_id === $user->id || $this->receiver_id === $user->id;
    }

    public function canBeDeletedBy(User $user): bool
    {
        return $this->sender_id === $user->id;
    }
}
