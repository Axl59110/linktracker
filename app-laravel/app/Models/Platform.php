<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'description',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the backlinks associated with this platform.
     */
    public function backlinks()
    {
        return $this->hasMany(Backlink::class);
    }

    /**
     * Scope a query to only include active platforms.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
