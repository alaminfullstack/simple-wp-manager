<?php

use Inertia\Inertia;
use Laravel\Fortify\Features;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\WordPressController;
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

    Route::get('/test-docker', function() {
        $process = new \Symfony\Component\Process\Process(['docker', 'ps']);
        $process->run();
        
        return response()->json([
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'error' => $process->getErrorOutput()
        ]);
    });

    Route::get('/test-storage', function() {
        $path = storage_path('wordpress-sites');
        $writable = is_writable($path);
        
        return response()->json([
            'path' => $path,
            'exists' => file_exists($path),
            'writable' => $writable
        ]);
    });

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
