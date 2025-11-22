<?php

use Inertia\Inertia;
use Laravel\Fortify\Features;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\ServerController;

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
});

require __DIR__.'/settings.php';
