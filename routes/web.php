<?php

use Inertia\Inertia;
use Laravel\Fortify\Features;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WordPressController;
use App\Http\Controllers\WordPressSiteController;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/test-docker', [DashboardController::class, 'test_docker']);
    Route::get('/test-storage', [DashboardController::class, 'test_storage']);

    // Server Management Routes
    Route::post('/servers/{server}/test', [ServerController::class, 'testConnection'])->name('servers.test');
    Route::post('/servers/{server}/install-monitor', [ServerController::class, 'installMonitorScript'])->name('servers.install-monitor');
    Route::resource('servers', ServerController::class);

    // WordPress Sites Routes
    Route::post('/wordpress/{site}/start', [WordPressController::class, 'start'])->name('wordpress.start');
    Route::post('/wordpress/{site}/stop', [WordPressController::class, 'stop'])->name('wordpress.stop');
    Route::get('/wordpress/{site}/logs', [WordPressController::class, 'logs'])->name('wordpress.logs');
    Route::resource('wordpress', WordPressController::class);
    
});

require __DIR__.'/settings.php';
