<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\SSHService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServerController extends Controller
{
    public function index()
    {
        $servers = Server::withCount('wordPressSites')->orderBy('created_at', 'desc')->get();
        
        return Inertia::render('Servers/Index', [
            'servers' => $servers
        ]);
    }

    public function create()
    {
        return Inertia::render('Servers/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'ssh_port' => 'required|integer|min:1|max:65535',
            'ssh_user' => 'required|string|max:255',
            'connection_type' => 'required|in:password,key',
            'ssh_password' => 'required_if:connection_type,password|nullable|string',
            'ssh_key' => 'required_if:connection_type,key|nullable|string',
        ]);

        try {
            $server = Server::create($validated);
            
            // Test connection
            $sshService = new SSHService($server);
            $testResult = $sshService->testConnection();
            
            if (!$testResult['success']) {
                $server->delete();
                return back()
                    ->withInput()
                    ->with('error', 'Connection test failed: ' . $testResult['message']);
            }
            
            return redirect()->route('servers.show', $server)
                ->with('success', 'Server added successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to add server: ' . $e->getMessage());
        }
    }

    public function show(Server $server)
    {
        $server->load(['wordPressSites' => function($query) {
            $query->latest();
        }]);
        
        return Inertia::render('Servers/Show', [
            'server' => $server
        ]);
    }

    public function edit(Server $server)
    {
        return Inertia::render('Servers/Edit', [
            'server' => $server
        ]);
    }

    public function update(Request $request, Server $server)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'ssh_port' => 'required|integer|min:1|max:65535',
            'ssh_user' => 'required|string|max:255',
            'connection_type' => 'required|in:password,key',
            'ssh_password' => 'nullable|string',
            'ssh_key' => 'nullable|string',
        ]);

        // Remove empty password/key fields
        if (empty($validated['ssh_password'])) {
            unset($validated['ssh_password']);
        }
        if (empty($validated['ssh_key'])) {
            unset($validated['ssh_key']);
        }

        try {
            $server->update($validated);
            
            return redirect()->route('servers.show', $server)
                ->with('success', 'Server updated successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update server: ' . $e->getMessage());
        }
    }

    public function destroy(Server $server)
    {
        try {
            $sitesCount = $server->wordPressSites()->count();
            
            if ($sitesCount > 0) {
                return back()->with('error', "Cannot delete server with {$sitesCount} active site(s). Delete the sites first.");
            }
            
            $server->delete();
            
            return redirect()->route('servers.index')
                ->with('success', 'Server deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete server: ' . $e->getMessage());
        }
    }

    public function testConnection(Server $server)
    {
        try {
            $sshService = new SSHService($server);
            $result = $sshService->testConnection();
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function installMonitorScript(Server $server)
    {
        try {
            $sshService = new SSHService($server);
            $sshService->connect();

            // Get the monitor script content
            $scriptPath = base_path('scripts/docker-monitor.sh');
            
            if (!file_exists($scriptPath)) {
                return back()->with('error', 'Monitor script not found');
            }

            $scriptContent = file_get_contents($scriptPath);
            
            // Replace placeholders
            $appUrl = config('app.url');
            $apiToken = $server->id; // You should generate proper API token
            
            $scriptContent = str_replace('YOUR_LARAVEL_APP_URL', $appUrl, $scriptContent);
            $scriptContent = str_replace('YOUR_API_TOKEN', $apiToken, $scriptContent);

            // Create temporary file
            $tempFile = sys_get_temp_dir() . '/docker-monitor-' . $server->id . '.sh';
            file_put_contents($tempFile, $scriptContent);

            // Upload script
            $sshService->uploadFile($tempFile, '/tmp/docker-monitor.sh');
            unlink($tempFile);

            // Install script
            $commands = [
                'sudo mv /tmp/docker-monitor.sh /usr/local/bin/docker-monitor.sh',
                'sudo chmod +x /usr/local/bin/docker-monitor.sh',
                'sudo touch /var/log/docker-monitor.log',
                'sudo chmod 664 /var/log/docker-monitor.log',
            ];

            foreach ($commands as $command) {
                $result = $sshService->execute($command);
                if (!$result['success']) {
                    throw new \Exception("Failed to execute: $command");
                }
            }

            // Setup cron job (every 5 minutes)
            $cronCommand = '*/5 * * * * /usr/local/bin/docker-monitor.sh';
            $sshService->execute("(crontab -l 2>/dev/null; echo '$cronCommand') | crontab -");

            $sshService->disconnect();

            return back()->with('success', 'Monitor script installed successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to install monitor script: ' . $e->getMessage());
        }
    }
}