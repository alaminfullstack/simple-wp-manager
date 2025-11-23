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

   
    Route::resource('sites', SiteController::class);

    // Server Management Routes
    Route::get('/servers', [ServerController::class, 'index'])->name('servers.index');
    Route::get('/servers/create', [ServerController::class, 'create'])->name('servers.create');
    Route::post('/servers', [ServerController::class, 'store'])->name('servers.store');
    Route::get('/servers/{server}', [ServerController::class, 'show'])->name('servers.show');
    Route::get('/servers/{server}/edit', [ServerController::class, 'edit'])->name('servers.edit');
    Route::put('/servers/{server}', [ServerController::class, 'update'])->name('servers.update');
    Route::delete('/servers/{server}', [ServerController::class, 'destroy'])->name('servers.destroy');
    Route::post('/servers/{server}/test', [ServerController::class, 'testConnection'])->name('servers.test');
    Route::post('/servers/{server}/install-monitor', [ServerController::class, 'installMonitorScript'])->name('servers.install-monitor');

    // WordPress Sites Routes
    Route::get('/wordpress', [WordPressController::class, 'index'])->name('wordpress.index');
    Route::get('/wordpress/create', [WordPressController::class, 'create'])->name('wordpress.create');
    Route::post('/wordpress', [WordPressController::class, 'store'])->name('wordpress.store');
    Route::get('/wordpress/{site}', [WordPressController::class, 'show'])->name('wordpress.show');
    Route::get('/wordpress/{site}/edit', [WordPressController::class, 'edit'])->name('wordpress.edit');
    Route::put('/wordpress/{site}', [WordPressController::class, 'update'])->name('wordpress.update');
    Route::post('/wordpress/{site}/start', [WordPressController::class, 'start'])->name('wordpress.start');
    Route::post('/wordpress/{site}/stop', [WordPressController::class, 'stop'])->name('wordpress.stop');
    Route::delete('/wordpress/{site}', [WordPressController::class, 'destroy'])->name('wordpress.destroy');
    Route::get('/wordpress/{site}/logs', [WordPressController::class, 'logs'])->name('wordpress.logs');
    
});

require __DIR__.'/settings.php';
