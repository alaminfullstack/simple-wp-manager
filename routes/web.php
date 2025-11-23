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

    Route::resource('servers', ServerController::class);
    Route::resource('sites', SiteController::class);

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
