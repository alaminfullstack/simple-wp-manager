<?php

use Inertia\Inertia;
use Laravel\Fortify\Features;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\WordPressSiteController;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::resource('servers', ServerController::class);
    Route::resource('sites', SiteController::class);

    Route::get('sites/{site}/start', [SiteController::class, 'start'])->name('sites.start');
    Route::get('sites/{site}/stop', [SiteController::class, 'stop'])->name('sites.stop');
    Route::get('sites/{site}/restart', [SiteController::class, 'restart'])->name('sites.restart');

    // WordPress Sites Management
    Route::resource('wordpress-sites', WordPressSiteController::class);
    
    // Container Control Actions
    Route::post('wordpress-sites/{site}/start', [WordPressSiteController::class, 'start'])
        ->name('wordpress-sites.start');
    Route::post('wordpress-sites/{site}/stop', [WordPressSiteController::class, 'stop'])
        ->name('wordpress-sites.stop');
    Route::post('wordpress-sites/{site}/restart', [WordPressSiteController::class, 'restart'])
        ->name('wordpress-sites.restart');
    
});

require __DIR__.'/settings.php';
