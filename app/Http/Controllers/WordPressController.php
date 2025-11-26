<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Server;
use Illuminate\Http\Request;
use App\Models\WordPressSite;
use App\Services\DockerService;
use App\Jobs\DeleteWordPressSite;
use App\Jobs\DeployWordPressSite;
use App\Jobs\UpdateWordPressSite;
use Illuminate\Support\Facades\Auth;

class WordPressController extends Controller
{
    private $dockerService;

    public function __construct(DockerService $dockerService)
    {
        $this->dockerService = $dockerService;
    }

    public function index()
    {
        $sites = WordPressSite::with('server')->where('user_id', Auth::id())->orderBy('created_at', 'desc')->get();
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
            //return redirect()->route('wordpress.index')->with('error', 'Docker is not running or Docker Compose is not available. Please start Docker Desktop.');
        }
        
        // Get all active servers
        $servers = Server::where(['status' => 'active', 'user_id' => Auth::id()])->get();
        
        return Inertia::render('WordPress/Create', [
            'dockerStatus' => $dockerStatus,
            'servers' => $servers
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'server_id' => 'nullable|exists:servers,id',
            'site_name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'port' => 'nullable|integer|min:1024|max:65535',
            'admin_email' => 'nullable|email',
            'admin_user' => 'nullable|string|max:255',
            'admin_password' => 'nullable|string|min:8',
            'db_name' => 'nullable|string|max:255',
            'db_user' => 'nullable|string|max:255',
            'db_password' => 'nullable|string|min:8',
        ]);

        try {
            $isRemote = !empty($validated['server_id']);
            
            // Only check Docker if deploying locally
            if (!$isRemote) {
                $dockerStatus = $this->dockerService->checkDockerAvailability();
                
                if (!$dockerStatus['can_create_sites']) {
                    return back()
                        ->withInput()
                        ->with('error', 'Docker Desktop is not running. Please start Docker Desktop to create local sites, or select a remote server.');
                }
            }
            
            // Check if using queue or direct deployment
            $useQueue = config('wordpress.use_queue', true); // Default to TRUE now
            
            if ($useQueue) {
                // Create site record with 'deploying' status
                $site = $this->createSiteRecord($validated);
                
                // Dispatch to queue - returns immediately
                DeployWordPressSite::dispatch($site->id, $validated);
                
                return redirect()->route('wordpress.show', $site)
                    ->with('success', 'WordPress site deployment started! Refresh the page in 1-2 minutes to see the status.');
            } else {
                // Direct deployment (may timeout)
                $site = $this->dockerService->createWordPressSite($validated);
                
                return redirect()->route('wordpress.show', $site)
                    ->with('success', 'WordPress site created successfully!');
            }
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create WordPress site: ' . $e->getMessage());
        }
    }

    public function createSiteRecord(array $data): WordPressSite
    {
        $server = isset($data['server_id']) ? Server::find($data['server_id']) : null;
        $isRemote = $server && !$server->isLocal();
        
        $containerName = 'wp_' . uniqid();
        $port = $this->dockerService->findAvailablePort($data['port'] ?? 8080, $server);

        return WordPressSite::create([
            'server_id' => $data['server_id'] ?? null,
            'is_remote' => $isRemote,
            'site_name' => $data['site_name'],
            'domain' => $data['domain'] ?? ($isRemote ? $server->ip_address : 'localhost'),
            'port' => $port,
            'container_name' => $containerName,
            'db_name' => $data['db_name'] ?? 'wordpress',
            'db_user' => $data['db_user'] ?? 'wpuser',
            'db_password' => $data['db_password'] ?? $this->generatePassword(),
            'db_root_password' => $this->generatePassword(),
            'admin_email' => $data['admin_email'] ?? 'admin@gmail.com',
            'admin_user' => $data['admin_user'] ?? 'admin',
            'admin_password' => $data['admin_password'] ?? $this->generatePassword(),
            'status' => 'deploying',
        ]);
    }

    private function generatePassword(int $length = 16): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        $max = strlen($characters) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, $max)];
        }
        
        return $password;
    }

    public function show($id)
    {
        $site = WordPressSite::findOrFail($id);
        $dockerStatus = $this->dockerService->checkDockerAvailability();
        $site->load('server');
        
        return Inertia::render('WordPress/Show', [
            'site' => $site,
            'dockerStatus' => $dockerStatus
        ]);
    }

    public function edit($id)
    {
        $site = WordPressSite::findOrFail($id);
        $dockerStatus = $this->dockerService->checkDockerAvailability();
        $site->load('server');

        // Get all active servers
        $servers = Server::where(['status' => 'active', 'user_id' => Auth::id()])->get();

        return Inertia::render('WordPress/Edit', [
            'site' => $site,
            'dockerStatus' => $dockerStatus,
            'servers' => $servers
        ]);
    }

    public function update(Request $request, $id)
    {
        $site = WordPressSite::findOrFail($id);

        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'port' => 'nullable|integer|min:1024|max:65535|unique:wordpress_sites,port,' . $site->id,
            'admin_email' => 'nullable|email',
            'admin_user' => 'nullable|string|max:255',
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
            // Check if critical changes require container recreation
            $needsRecreation = 
                (isset($validated['port']) && $validated['port'] != $site->port) ||
                (isset($validated['db_name']) && $validated['db_name'] != $site->db_name) ||
                (isset($validated['db_user']) && $validated['db_user'] != $site->db_user) ||
                (isset($validated['db_password']) && $validated['db_password'] != $site->db_password);

            $useQueue = config('wordpress.use_queue', true);

            if ($useQueue && $needsRecreation) {
                // Update with deploying status
                $site->update(['status' => 'deploying']);
                
                // Dispatch update job
                UpdateWordPressSite::dispatch($site->id, $validated, $needsRecreation);
                
                return redirect()->route('wordpress.show', $site)
                    ->with('success', 'WordPress site update started! Refresh the page in a moment to see the status.');
            } else {
                // Simple update or direct update
                $site->update($validated);
                
                return redirect()->route('wordpress.show', $site)
                    ->with('success', 'WordPress site updated successfully!');
            }
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

    public function destroy($id)
    {
        $site = WordPressSite::findOrFail($id);
        try {
            $useQueue = config('wordpress.use_queue', true);

            if ($useQueue) {
                // Mark as deleting
                $site->update(['status' => 'deleting']);
                
                // Dispatch delete job
                DeleteWordPressSite::dispatch(
                    $site->id,
                    $site->is_remote,
                    $site->server_id,
                    $site->container_name
                );
                
                return redirect()->route('wordpress.index')
                    ->with('success', 'Site deletion started! It will be removed shortly.');
            } else {
                // Direct deletion
                $this->dockerService->deleteSite($site);
                
                return redirect()->route('wordpress.index')
                    ->with('success', 'Site deleted successfully!');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete site: ' . $e->getMessage());
        }
    }

    public function logs($id)
    {
        $site = WordPressSite::findOrFail($id);
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