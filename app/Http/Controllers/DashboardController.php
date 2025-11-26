<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Server;
use Illuminate\Http\Request;
use App\Models\WordPressSite;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function dashboard(){
        $user = Auth::user();
        
        // Get all sites for the user
        $allSites = WordPressSite::where('user_id', $user->id)->get();
        
        // Calculate statistics
        $totalSites = $allSites->count();
        $runningSites = $allSites->where('status', 'running')->count();
        $stoppedSites = $allSites->where('status', 'stopped')->count();
        $deployingSites = $allSites->whereIn('status', ['deploying', 'creating'])->count();
        $errorSites = $allSites->whereIn('status', ['error', 'failed'])->count();
        
        // Get recent sites (last 6)
        $recentSites = WordPressSite::where('user_id', $user->id)
            ->with('server')
            ->latest()
            ->limit(6)
            ->get();
        
        // Get all servers
        $servers = Server::where('user_id', $user->id)
            ->withCount('wordPressSites')
            ->latest()
            ->get();
        
        return Inertia::render('dashboard', [
            'totalSites' => $totalSites,
            'totalServers' => $servers->count(),
            'runningSites' => $runningSites,
            'stoppedSites' => $stoppedSites,
            'deployingSites' => $deployingSites,
            'errorSites' => $errorSites,
            'recentSites' => $recentSites,
            'servers' => $servers,
        ]);
    }

    public function test_docker(){
        $process = new \Symfony\Component\Process\Process(['docker', 'ps']);
        $process->run();
        
        return response()->json([
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'error' => $process->getErrorOutput()
        ]);
    }

    public function test_storage() {
        $path = storage_path('wordpress-sites');
        $writable = is_writable($path);
        
        return response()->json([
            'path' => $path,
            'exists' => file_exists($path),
            'writable' => $writable
        ]);
    }
}
