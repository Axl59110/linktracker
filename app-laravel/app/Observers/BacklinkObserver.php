<?php

namespace App\Observers;

use App\Models\Backlink;
use Illuminate\Support\Facades\Cache;

/**
 * Observer STORY-041 : Invalide le cache des stats dashboard quand un backlink change
 */
class BacklinkObserver
{
    public function created(Backlink $backlink): void
    {
        Cache::forget('dashboard_stats');
    }

    public function updated(Backlink $backlink): void
    {
        Cache::forget('dashboard_stats');
    }

    public function deleted(Backlink $backlink): void
    {
        Cache::forget('dashboard_stats');
    }
}
