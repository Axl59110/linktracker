<?php

use Illuminate\Support\Facades\Route;

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

// NEW DESIGN PREVIEW - Route test pour voir le nouveau layout Blade
Route::get('/dashboard-preview', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard.preview');

// SPA entry point - all routes handled by Vue Router
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
