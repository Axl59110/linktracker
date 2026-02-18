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

    protected $attributes = [
        'is_active' => true,
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (Platform $platform) {
            $platform->backlinks()->update(['platform_id' => null]);
        });
    }

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
