<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;

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

// Dashboard principale avec nouveau layout Blade
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Projects - CRUD complet avec nouveau layout Blade
Route::resource('projects', ProjectController::class);

// TODO: Ajouter routes Blade pour :
// - /backlinks (global backlinks list + CRUD)
// - /alerts (EPIC-004)
// - /orders (EPIC-006)
// - /settings (EPIC-008)

// SPA entry point - all routes handled by Vue Router (ancien système)
// IMPORTANT: Cette route catch-all doit rester en dernier
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
