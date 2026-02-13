<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    // Types d'alertes
    const TYPE_BACKLINK_LOST = 'backlink_lost';
    const TYPE_BACKLINK_CHANGED = 'backlink_changed';
    const TYPE_BACKLINK_RECOVERED = 'backlink_recovered';

    // Niveaux de sévérité
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    protected $fillable = [
        'backlink_id',
        'type',
        'severity',
        'title',
        'message',
        'metadata',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the backlink associated with this alert.
     */
    public function backlink()
    {
        return $this->belongsTo(Backlink::class);
    }

    /**
     * Scope a query to only include unread alerts.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope a query to only include read alerts.
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by severity.
     */
    public function scopeOfSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope a query to get recent alerts.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Mark this alert as read.
     */
    public function markAsRead(): bool
    {
        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark this alert as unread.
     */
    public function markAsUnread(): bool
    {
        return $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Get the badge color for the alert type (for UI).
     */
    public function getTypeBadgeColorAttribute(): string
    {
        return match($this->type) {
            self::TYPE_BACKLINK_LOST => 'danger',
            self::TYPE_BACKLINK_CHANGED => 'warning',
            self::TYPE_BACKLINK_RECOVERED => 'success',
            default => 'neutral',
        };
    }

    /**
     * Get the badge color for the severity (for UI).
     */
    public function getSeverityBadgeColorAttribute(): string
    {
        return match($this->severity) {
            self::SEVERITY_CRITICAL => 'danger',
            self::SEVERITY_HIGH => 'warning',
            self::SEVERITY_MEDIUM => 'brand',
            self::SEVERITY_LOW => 'neutral',
            default => 'neutral',
        };
    }

    /**
     * Get the icon for the alert type.
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            self::TYPE_BACKLINK_LOST => '⚠️',
            self::TYPE_BACKLINK_CHANGED => '🔄',
            self::TYPE_BACKLINK_RECOVERED => '✅',
            default => '🔔',
        };
    }

    /**
     * Get the type label (for UI).
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_BACKLINK_LOST => 'Backlink perdu',
            self::TYPE_BACKLINK_CHANGED => 'Backlink modifié',
            self::TYPE_BACKLINK_RECOVERED => 'Backlink récupéré',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }
}
