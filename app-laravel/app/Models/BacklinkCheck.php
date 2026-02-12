<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BacklinkCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'backlink_id',
        'http_status',
        'is_present',
        'anchor_text',
        'rel_attributes',
        'response_time',
        'checked_at',
        'error_message',
    ];

    protected $casts = [
        'is_present' => 'boolean',
        'checked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the backlink that was checked.
     */
    public function backlink()
    {
        return $this->belongsTo(Backlink::class);
    }

    /**
     * Scope a query to get the latest check for each backlink.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('checked_at', 'desc');
    }

    /**
     * Determine if the check was successful (HTTP 2xx).
     */
    public function isSuccessful(): bool
    {
        return $this->http_status >= 200 && $this->http_status < 300;
    }

    /**
     * Determine if the backlink was found in the page.
     */
    public function wasFound(): bool
    {
        return $this->is_present === true;
    }
}
