<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\BacklinkController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\WebhookSettingsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// NEW BLADE ROUTES - SaaS UI Redesign (EPIC-013)

// Home page - Redirect to dashboard
Route::get('/', function () {
    return redirect('/dashboard');
});

// Dashboard principale avec nouveau layout Blade
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Projects - CRUD complet avec nouveau layout Blade
Route::resource('projects', ProjectController::class);

// Backlinks - CRUD complet avec nouveau layout Blade
// Rate limiting sur index pour éviter DoS via filtres/recherche
Route::resource('backlinks', BacklinkController::class)
    ->middleware(['throttle:60,1']);

// Vérification manuelle d'un backlink
Route::post('/backlinks/{backlink}/check', [BacklinkController::class, 'check'])
    ->name('backlinks.check')
    ->middleware(['throttle:5,1']); // Limiter à 5 vérifications manuelles par minute

// Platforms - Gestion des plateformes d'achat de liens
Route::resource('platforms', PlatformController::class)->except(['show']);

// Alerts - Système d'alertes pour backlinks (EPIC-004)
Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
Route::patch('/alerts/{alert}/mark-read', [AlertController::class, 'markAsRead'])->name('alerts.mark-read');
Route::patch('/alerts/mark-all-read', [AlertController::class, 'markAllAsRead'])->name('alerts.mark-all-read');
Route::delete('/alerts/{alert}', [AlertController::class, 'destroy'])->name('alerts.destroy');
Route::delete('/alerts/destroy-all-read', [AlertController::class, 'destroyAllRead'])->name('alerts.destroy-all-read');

// Settings - Webhook configurable (STORY-019)
Route::get('/settings/webhook', [WebhookSettingsController::class, 'show'])->name('settings.webhook');
Route::put('/settings/webhook', [WebhookSettingsController::class, 'update'])->name('settings.webhook.update');
Route::post('/settings/webhook/test', [WebhookSettingsController::class, 'test'])->name('settings.webhook.test');
Route::get('/settings/webhook/generate-secret', [WebhookSettingsController::class, 'generateSecret'])->name('settings.webhook.generate-secret');

// Page "En construction" pour fonctionnalités futures
Route::view('/under-construction', 'pages.under-construction')->name('pages.under-construction');

// TODO: Ajouter routes Blade pour :
// - /alerts (EPIC-004)
// - /orders (EPIC-006)
// - /settings (EPIC-008)

// SPA entry point - all routes handled by Vue Router (ancien système)
// IMPORTANT: Cette route catch-all doit rester en dernier
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
