<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BacklinkController;
use App\Http\Controllers\Api\V1\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API v1 Routes
Route::prefix('v1')->group(function () {
    // Authentication Routes (Public)
    Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');

    // Protected Routes (Require Authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
        Route::get('/auth/user', [AuthController::class, 'user'])->name('api.v1.auth.user');

        // Projects CRUD
        Route::apiResource('projects', ProjectController::class);

        // Backlinks CRUD (nested under projects)
        Route::apiResource('projects.backlinks', BacklinkController::class)->except(['index', 'store']);
        Route::get('projects/{project}/backlinks', [BacklinkController::class, 'index'])->name('projects.backlinks.index');
        Route::post('projects/{project}/backlinks', [BacklinkController::class, 'store'])->name('projects.backlinks.store');
    });
});
