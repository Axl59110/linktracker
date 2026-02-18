<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'platform_id',
        'backlink_id',
        'status',
        'target_url',
        'source_url',
        'anchor_text',
        'tier_level',
        'spot_type',
        'price',
        'currency',
        'invoice_paid',
        'ordered_at',
        'expected_at',
        'published_at',
        'contact_name',
        'contact_email',
        'notes',
    ];

    protected $casts = [
        'invoice_paid' => 'boolean',
        'ordered_at'   => 'date',
        'expected_at'  => 'date',
        'published_at' => 'date',
        'price'        => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    public function backlink()
    {
        return $this->belongsTo(Backlink::class);
    }

    /**
     * Retourne la couleur du badge selon le statut.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending'     => 'warning',
            'in_progress' => 'brand',
            'published'   => 'success',
            'cancelled'   => 'neutral',
            'refunded'    => 'danger',
            default       => 'neutral',
        };
    }

    /**
     * Retourne le libellé du statut.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'     => 'En attente',
            'in_progress' => 'En cours',
            'published'   => 'Publié',
            'cancelled'   => 'Annulé',
            'refunded'    => 'Remboursé',
            default       => ucfirst($this->status),
        };
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
