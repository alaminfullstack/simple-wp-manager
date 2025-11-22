<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Inertia\Inertia;
use App\Models\Server;
use Illuminate\Http\Request;
use App\Services\DockerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SiteController extends Controller
{
    protected $dockerService;
    
    public function __construct(DockerService $dockerService)
    {
        $this->dockerService = $dockerService;
    }
    
    public function index()
    {
        $sites = Site::with('server')->get();
        
        return Inertia::render('Sites/Index', [
            'sites' => $sites
        ]);
    }
    
    public function create()
    {
        $servers = Server::where('active', true)->get();
        
        return Inertia::render('Sites/Create', [
            'servers' => $servers
        ]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'server_id' => 'required|exists:servers,id',
            'domain' => 'required|string|max:255',
            'container_name' => 'required|string|max:255|unique:sites,container_name',
            'database_name' => 'required|string|max:255',
            'database_user' => 'required|string|max:255',
            'database_password' => 'required|string|min:8',
            'admin_username' => 'required|string|max:255',
            'admin_password' => 'required|string|min:8',
            'admin_email' => 'required|email',
            'ssl.enabled' => 'boolean',
            'firewall_rules.allow_http' => 'boolean',
            'firewall_rules.allow_https' => 'boolean',
            'firewall_rules.allow_ssh' => 'boolean',
        ]);
        
        $validated['status'] = 'deploying';
        $validated['user_id'] = Auth::id();
        
        $site = Site::create($validated);
        
        // Deploy the site in the background
        dispatch(function () use ($site) {
            $this->dockerService->deploySite($site);
        });
        
        return redirect()->route('sites.index')
            ->with('success', 'Site is being deployed. This may take a few minutes.');
    }
    
    public function show(Site $site)
    {
        $site->load('server');
        
        return Inertia::render('Sites/Show', [
            'site' => $site
        ]);
    }
    
    public function edit(Site $site)
    {
        $servers = Server::where('active', true)->get();
        $site->load('server');
        
        return Inertia::render('Sites/Edit', [
            'site' => $site,
            'servers' => $servers
        ]);
    }
    
    public function update(Request $request, Site $site)
    {
        $validated = $request->validate([
            'server_id' => 'required|exists:servers,id',
            'domain' => 'required|string|max:255',
            'container_name' => 'required|string|max:255|unique:sites,container_name,' . $site->id,
            'database_name' => 'required|string|max:255',
            'database_user' => 'required|string|max:255',
            'database_password' => 'required|string|min:8',
            'admin_username' => 'required|string|max:255',
            'admin_password' => 'required|string|min:8',
            'admin_email' => 'required|email',
            'ssl.enabled' => 'boolean',
            'firewall_rules.allow_http' => 'boolean',
            'firewall_rules.allow_https' => 'boolean',
            'firewall_rules.allow_ssh' => 'boolean',
        ]);
        
        $site->update($validated);
        
        // Update the site in the background
        dispatch(function () use ($site) {
            $this->dockerService->updateSite($site);
        });
        
        return redirect()->route('sites.index')
            ->with('success', 'Site is being updated. This may take a few minutes.');
    }
    
    public function destroy(Site $site)
    {
        // Remove the site in the background
        dispatch(function () use ($site) {
            $this->dockerService->removeSite($site);
        });
        
        $site->delete();
        
        return redirect()->route('sites.index')
            ->with('success', 'Site is being removed. This may take a few minutes.');
    }
    
    public function start(Site $site)
    {
        dispatch(function () use ($site) {
            $this->dockerService->startSite($site);
        });
        
        return redirect()->route('sites.index')
            ->with('success', 'Site is being started. This may take a few minutes.');
    }
    
    public function stop(Site $site)
    {
        dispatch(function () use ($site) {
            $this->dockerService->stopSite($site);
        });
        
        return redirect()->route('sites.index')
            ->with('success', 'Site is being stopped. This may take a few minutes.');
    }
    
    public function restart(Site $site)
    {
        dispatch(function () use ($site) {
            $this->dockerService->restartSite($site);
        });
        
        return redirect()->route('sites.index')
            ->with('success', 'Site is being restarted. This may take a few minutes.');
    }
}