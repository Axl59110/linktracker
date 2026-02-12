<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Backlink extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_url',
        'target_url',
        'anchor_text',
        'status',
        'http_status',
        'rel_attributes',
        'is_dofollow',
        'first_seen_at',
        'last_checked_at',
    ];

    protected $casts = [
        'is_dofollow' => 'boolean',
        'first_seen_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the project that owns the backlink.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
