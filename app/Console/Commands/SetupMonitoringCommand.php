<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WordPressSite;
use App\Services\MonitoringScriptDeployer;
use Illuminate\Console\Command;

class SetupMonitoringCommand extends Command
{
    protected $signature = 'monitoring:setup {site_id?} {--all}';
    protected $description = 'Deploy monitoring script to remote servers';

    public function handle(MonitoringScriptDeployer $deployer): int
    {
        if ($this->option('all')) {
            return $this->setupAllSites($deployer);
        }

        $siteId = $this->argument('site_id');
        
        if (!$siteId) {
            $this->error('Please provide a site ID or use --all flag');
            return 1;
        }

        return $this->setupSingleSite($siteId, $deployer);
    }

    protected function setupSingleSite(int $siteId, MonitoringScriptDeployer $deployer): int
    {
        $site = WordPressSite::find($siteId);

        if (!$site) {
            $this->error("Site with ID {$siteId} not found");
            return 1;
        }

        $this->info("Setting up monitoring for: {$site->name}");

        // Generate or get API token
        $token = $this->getOrCreateToken();

        // Deploy script
        $result = $deployer->deploy($site, $token);

        if ($result['success']) {
            $this->info("✓ {$result['message']}");
            return 0;
        } else {
            $this->error("✗ {$result['message']}");
            return 1;
        }
    }

    protected function setupAllSites(MonitoringScriptDeployer $deployer): int
    {
        $sites = WordPressSite::all();

        if ($sites->isEmpty()) {
            $this->info('No sites found');
            return 0;
        }

        $this->info("Setting up monitoring for {$sites->count()} sites...");

        $token = $this->getOrCreateToken();
        $success = 0;
        $failed = 0;

        foreach ($sites as $site) {
            $this->line("Processing: {$site->name}...");

            $result = $deployer->deploy($site, $token);

            if ($result['success']) {
                $this->info("  ✓ Success");
                $success++;
            } else {
                $this->error("  ✗ Failed: {$result['message']}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Setup completed: {$success} successful, {$failed} failed");

        return $failed > 0 ? 1 : 0;
    }

    protected function getOrCreateToken(): string
    {
        // Try to find monitoring user
        $user = User::where('email', 'monitoring@system.local')->first();

        if (!$user) {
            $this->line('Creating monitoring user...');
            $user = User::create([
                'name' => 'Monitoring System',
                'email' => 'monitoring@system.local',
                'password' => bcrypt(str()->random(32)),
            ]);
        }

        // Create or get token
        $tokenName = 'monitoring-script';
        
        // Delete old tokens
        $user->tokens()->where('name', $tokenName)->delete();

        // Create new token
        $token = $user->createToken($tokenName)->plainTextToken;

        $this->info("API Token: {$token}");
        $this->newLine();

        return $token;
    }
}