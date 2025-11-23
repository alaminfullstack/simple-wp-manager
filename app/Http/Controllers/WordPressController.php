<?php

namespace App\Http\Controllers;

use App\Models\WordPressSite;
use App\Services\DockerService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WordPressController extends Controller
{
    private $dockerService;

    public function __construct(DockerService $dockerService)
    {
        $this->dockerService = $dockerService;
    }

    public function index()
    {
        $sites = WordPressSite::orderBy('created_at', 'desc')->get();
        $dockerStatus = $this->dockerService->checkDockerAvailability();
        
        return Inertia::render('WordPress/Index', [
            'sites' => $sites,
            'dockerStatus' => $dockerStatus
        ]);
    }

    public function create()
    {
        $dockerStatus = $this->dockerService->checkDockerAvailability();
        
        if (!$dockerStatus['can_create_sites']) {
            return redirect()->route('wordpress.index')
                ->with('error', 'Docker is not running or Docker Compose is not available. Please start Docker Desktop.');
        }
        
        return Inertia::render('WordPress/Create', [
            'dockerStatus' => $dockerStatus
        ]);
    }

    public function store(Request $request)
    {
        // Check Docker availability first
        $dockerStatus = $this->dockerService->checkDockerAvailability();
        
        if (!$dockerStatus['can_create_sites']) {
            return back()->with('error', 'Docker is not running. Please start Docker Desktop and try again.');
        }
        
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'port' => 'nullable|integer|min:1024|max:65535',
            'admin_email' => 'required|email',
            'admin_user' => 'required|string|max:255',
            'admin_password' => 'required|string|min:8',
            'db_name' => 'nullable|string|max:255',
            'db_user' => 'nullable|string|max:255',
            'db_password' => 'nullable|string|min:8',
        ]);

        try {
            $site = $this->dockerService->createWordPressSite($validated);
            
            return redirect()->route('wordpress.show', $site)
                ->with('success', 'WordPress site created successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create WordPress site: ' . $e->getMessage());
        }
    }

    public function show(WordPressSite $site)
    {
        $dockerStatus = $this->dockerService->checkDockerAvailability();
        
        return Inertia::render('WordPress/Show', [
            'site' => $site,
            'dockerStatus' => $dockerStatus
        ]);
    }

    public function edit(WordPressSite $site)
    {
        $dockerStatus = $this->dockerService->checkDockerAvailability();
        
        return Inertia::render('WordPress/Edit', [
            'site' => $site,
            'dockerStatus' => $dockerStatus
        ]);
    }

    public function update(Request $request, WordPressSite $site)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'port' => 'nullable|integer|min:1024|max:65535|unique:wordpress_sites,port,' . $site->id,
            'admin_email' => 'required|email',
            'admin_user' => 'required|string|max:255',
            'admin_password' => 'nullable|string|min:8',
            'db_name' => 'nullable|string|max:255',
            'db_user' => 'nullable|string|max:255',
            'db_password' => 'nullable|string|min:8',
        ]);

        // Remove empty password fields
        if (empty($validated['admin_password'])) {
            unset($validated['admin_password']);
        }
        if (empty($validated['db_password'])) {
            unset($validated['db_password']);
        }

        try {
            $this->dockerService->updateWordPressSite($site, $validated);
            
            return redirect()->route('wordpress.show', $site)
                ->with('success', 'WordPress site updated successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update WordPress site: ' . $e->getMessage());
        }
    }

    public function start(WordPressSite $site)
    {
        try {
            $this->dockerService->startSite($site);
            return back()->with('success', 'Site started successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to start site: ' . $e->getMessage());
        }
    }

    public function stop(WordPressSite $site)
    {
        try {
            $this->dockerService->stopSite($site);
            return back()->with('success', 'Site stopped successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to stop site: ' . $e->getMessage());
        }
    }

    public function destroy(WordPressSite $site)
    {
        try {
            $this->dockerService->deleteSite($site);
            return redirect()->route('wordpress.index')
                ->with('success', 'Site deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete site: ' . $e->getMessage());
        }
    }

    public function logs(WordPressSite $site)
    {
        try {
            $logs = $this->dockerService->getSiteLogs($site);
            return response()->json(['logs' => $logs]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function checkDocker()
    {
        $status = $this->dockerService->checkDockerAvailability();
        return response()->json($status);
    }
}