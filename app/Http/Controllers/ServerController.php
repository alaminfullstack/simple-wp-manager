<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ServerController extends Controller
{
    public function index()
    {
        $servers = Server::withCount('sites')->get();
        
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
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'private_key' => 'nullable|string',
            'password' => 'nullable|string',
            'path' => 'required|string|max:255',
            'active' => 'boolean',
        ]);

        $validated['user_id'] = Auth::id();
        
        Server::create($validated);
        
        return redirect()->route('servers.index')
            ->with('success', 'Server created successfully.');
    }
    
    public function show(Server $server)
    {
        $server->load('sites');
        
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
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'private_key' => 'nullable|string',
            'password' => 'nullable|string',
            'path' => 'required|string|max:255',
            'active' => 'boolean',
        ]);
        
        $server->update($validated);
        
        return redirect()->route('servers.index')
            ->with('success', 'Server updated successfully.');
    }
    
    public function destroy(Server $server)
    {
        $server->delete();
        
        return redirect()->route('servers.index')
            ->with('success', 'Server deleted successfully.');
    }
}