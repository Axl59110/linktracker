<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\BacklinkController;
use App\Http\Controllers\PlatformController;

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

// Platforms - Gestion des plateformes d'achat de liens
Route::resource('platforms', PlatformController::class)->except(['show']);

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
