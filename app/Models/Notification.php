<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    // Notification types
    public const TYPE_BUDGET_WARNING = 'budget_warning';
    public const TYPE_BUDGET_EXCEEDED = 'budget_exceeded';
    public const TYPE_INVOICE_CLOSED = 'invoice_closed';
    public const TYPE_INVOICE_DUE_SOON = 'invoice_due_soon';
    public const TYPE_RECURRING_GENERATED = 'recurring_generated';
    public const TYPE_RECURRING_FAILED = 'recurring_failed';
    public const TYPE_IMPORT_COMPLETED = 'import_completed';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for read notifications.
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope for specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if notification is read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Mark as read.
     */
    public function markAsRead(): void
    {
        if (!$this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Get icon based on type.
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_BUDGET_WARNING => '⚠️',
            self::TYPE_BUDGET_EXCEEDED => '🔴',
            self::TYPE_INVOICE_CLOSED => '📋',
            self::TYPE_INVOICE_DUE_SOON => '⏰',
            self::TYPE_RECURRING_GENERATED => '🔄',
            self::TYPE_RECURRING_FAILED => '❌',
            self::TYPE_IMPORT_COMPLETED => '✅',
            default => '🔔',
        };
    }
}
