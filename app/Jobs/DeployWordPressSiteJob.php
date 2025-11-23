<?php

namespace App\Jobs;

use App\Models\WordPressSite;
use App\Services\SSHService;
use App\Services\DockerServices;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeployWordPressSiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $site;

    public function __construct(
        WordPressSite $site
    ) {
        $this->site = $site;
    }

    public function handle(): void
    {
        try {
            // Update status to deploying
            $this->site->update([
                'status' => 'deploying',
                'status_message' => 'Initializing deployment...',
            ]);

            // Connect to server via SSH
            $ssh = new SSHService($this->site->server_ip, $this->site->server_port);
            
            if ($this->site->server_ssh_key) {
                $ssh->connectWithKey($this->site->server_username, $this->site->server_ssh_key);
            } else {
                $ssh->connectWithPassword($this->site->server_username, $this->site->server_password);
            }

            // Check if Docker is installed
            if (!$ssh->isDockerInstalled()) {
                throw new Exception('Docker is not installed on the server');
            }

            if (!$ssh->isDockerComposeInstalled()) {
                throw new Exception('Docker Compose is not installed on the server');
            }

            // Deploy the site
            $docker = new DockerServices($ssh);
            $result = $docker->deploy($this->site);

            if (!$result['success']) {
                throw new Exception($result['message']);
            }

            // Update site status
            $this->site->update([
                'status' => 'running',
                'status_message' => 'Site deployed and running successfully',
                'last_checked_at' => now(),
            ]);

            Log::info("WordPress site deployed successfully", [
                'site_id' => $this->site->id,
                'domain' => $this->site->domain,
            ]);

            $ssh->disconnect();

        } catch (Exception $e) {
            Log::error("Failed to deploy WordPress site", [
                'site_id' => $this->site->id,
                'domain' => $this->site->domain,
                'error' => $e->getMessage(),
            ]);

            $this->site->update([
                'status' => 'failed',
                'status_message' => 'Deployment failed: ' . $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        $this->site->update([
            'status' => 'failed',
            'status_message' => 'Deployment failed after multiple attempts: ' . $exception->getMessage(),
        ]);
    }
}