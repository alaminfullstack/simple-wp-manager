<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWordPressSiteRequest;
use App\Http\Requests\UpdateWordPressSiteRequest;
use App\Jobs\DeployWordPressSiteJob;
use App\Jobs\ManageContainerJob;
use App\Models\WordPressSite;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WordPressSiteController extends Controller
{
    /**
     * Display a listing of WordPress sites.
     */
    public function index(): Response
    {
        $sites = WordPressSite::orderBy('created_at', 'desc')
            ->get()
            ->map(function ($site) {
                return [
                    'id' => $site->id,
                    'name' => $site->name,
                    'domain' => $site->domain,
                    'url' => $site->url,
                    'container_name' => $site->container_name,
                    'status' => $site->status,
                    'status_message' => $site->status_message,
                    'last_checked_at' => $site->last_checked_at?->diffForHumans(),
                    'ssl_enabled' => $site->ssl_enabled,
                    'wp_version' => $site->wp_version,
                    'masked_server_ip' => $site->masked_server_ip,
                    'created_at' => $site->created_at->format('M d, Y'),
                ];
            });

        return Inertia::render('Wordpress/Index', [
            'sites' => $sites,
        ]);
    }

    /**
     * Show the form for creating a new WordPress site.
     */
    public function create(): Response
    {
        return Inertia::render('Wordpress/Create');
    }

    /**
     * Store a newly created WordPress site.
     */
    public function store(StoreWordPressSiteRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Generate container name from domain
        $validated['container_name'] = 'wp_' . str_replace(['.', '-'], '_', $validated['domain']);

        // Create the site
        $site = WordPressSite::create($validated);

        // Dispatch deployment job
        DeployWordPressSiteJob::dispatch($site);

        return redirect()->route('wordpress-sites.index')
            ->with('success', 'WordPress site created successfully. Deployment started.');
    }

    /**
     * Display the specified WordPress site.
     */
    public function show(WordPressSite $site): Response
    {
        return Inertia::render('Wordpress/Show', [
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'domain' => $site->domain,
                'url' => $site->url,
                'container_name' => $site->container_name,
                'container_port' => $site->container_port,
                'status' => $site->status,
                'status_message' => $site->status_message,
                'last_checked_at' => $site->last_checked_at?->format('Y-m-d H:i:s'),
                'ssl_enabled' => $site->ssl_enabled,
                'wp_version' => $site->wp_version,
                'server_ip' => $site->server_ip,
                'server_port' => $site->server_port,
                'server_username' => $site->server_username,
                'db_name' => $site->db_name,
                'db_user' => $site->db_user,
                'db_host' => $site->db_host,
                'created_at' => $site->created_at,
                'updated_at' => $site->updated_at,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified WordPress site.
     */
    public function edit(WordPressSite $site): Response
    {
        return Inertia::render('Wordpress/Edit', [
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'domain' => $site->domain,
                'wp_version' => $site->wp_version,
                'container_port' => $site->container_port,
                'ssl_enabled' => $site->ssl_enabled,
                'server_ip' => $site->server_ip,
                'server_port' => $site->server_port,
                'server_username' => $site->server_username,
            ],
        ]);
    }

    /**
     * Update the specified WordPress site.
     */
    public function update(UpdateWordPressSiteRequest $request, WordPressSite $site): RedirectResponse
    {
        $validated = $request->validated();

        // Update the site
        $site->update($validated);

        // If critical settings changed, redeploy
        if ($request->has(['domain', 'wp_version', 'container_port'])) {
            DeployWordPressSiteJob::dispatch($site);
            return redirect()->route('sites.show', $site)
                ->with('success', 'Site updated. Redeployment started.');
        }

        return redirect()->route('wordpress-sites.show', $site)
            ->with('success', 'WordPress site updated successfully.');
    }

    /**
     * Remove the specified WordPress site.
     */
    public function destroy(WordPressSite $site): RedirectResponse
    {
        // Dispatch job to remove container
        ManageContainerJob::dispatch($site, 'remove');

        return redirect()->route('sites.index')
            ->with('success', 'WordPress site deletion initiated.');
    }

    /**
     * Start a WordPress site container.
     */
    public function start(WordPressSite $site): RedirectResponse
    {
        ManageContainerJob::dispatch($site, 'start');

        return back()->with('success', 'Container start initiated.');
    }

    /**
     * Stop a WordPress site container.
     */
    public function stop(WordPressSite $site): RedirectResponse
    {
        ManageContainerJob::dispatch($site, 'stop');

        return back()->with('success', 'Container stop initiated.');
    }

    /**
     * Restart a WordPress site container.
     */
    public function restart(WordPressSite $site): RedirectResponse
    {
        ManageContainerJob::dispatch($site, 'restart');

        return back()->with('success', 'Container restart initiated.');
    }
}