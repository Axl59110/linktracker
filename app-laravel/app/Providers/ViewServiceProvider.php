<?php

namespace App\Providers;

use App\Models\Alert;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Partager le nombre d'alertes non lues avec toutes les vues
        View::composer('*', function ($view) {
            $view->with('unreadAlertsCount', Alert::unread()->count());
        });
    }
}
