<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusLog extends Model
{
    protected $fillable = [
        'order_id',
        'old_status',
        'new_status',
        'notes',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    private static array $labels = [
        'pending'     => 'En attente',
        'in_progress' => 'En cours',
        'published'   => 'Publié',
        'cancelled'   => 'Annulé',
        'refunded'    => 'Remboursé',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getOldStatusLabelAttribute(): string
    {
        return self::$labels[$this->old_status ?? ''] ?? ucfirst($this->old_status ?? '—');
    }

    public function getNewStatusLabelAttribute(): string
    {
        return self::$labels[$this->new_status] ?? ucfirst($this->new_status);
    }

    public function getNewStatusBadgeAttribute(): string
    {
        return match($this->new_status) {
            'pending'     => 'warning',
            'in_progress' => 'brand',
            'published'   => 'success',
            'cancelled'   => 'neutral',
            'refunded'    => 'danger',
            default       => 'neutral',
        };
    }
}
