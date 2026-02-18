<?php

namespace App\Providers;

use App\Models\Backlink;
use App\Observers\BacklinkObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Backlink::observe(BacklinkObserver::class);
    }
}
