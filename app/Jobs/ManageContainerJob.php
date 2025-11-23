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

class ManageContainerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 2;

    public function __construct(
        public WordPressSite $site,
        public string $action // start, stop, restart, remove
    ) {}

    public function handle(): void
    {
        try {
            // Connect to server via SSH
            $ssh = new SSHService($this->site->server_ip, $this->site->server_port);
            
            if ($this->site->server_ssh_key) {
                $ssh->connectWithKey($this->site->server_username, $this->site->server_ssh_key);
            } else {
                $ssh->connectWithPassword($this->site->server_username, $this->site->server_password);
            }

            $docker = new DockerServices($ssh);
            $result = null;

            // Perform the requested action
            switch ($this->action) {
                case 'start':
                    $result = $docker->start($this->site);
                    $newStatus = $result['success'] ? 'running' : 'failed';
                    break;

                case 'stop':
                    $result = $docker->stop($this->site);
                    $newStatus = $result['success'] ? 'stopped' : 'failed';
                    break;

                case 'restart':
                    $result = $docker->restart($this->site);
                    $newStatus = $result['success'] ? 'running' : 'failed';
                    break;

                case 'remove':
                    $result = $docker->remove($this->site, true);
                    if ($result['success']) {
                        $this->site->delete(); // Soft delete
                        $ssh->disconnect();
                        return;
                    }
                    $newStatus = 'failed';
                    break;

                default:
                    throw new Exception("Invalid action: {$this->action}");
            }

            if (!$result['success']) {
                throw new Exception($result['message']);
            }

            // Update site status
            $this->site->update([
                'status' => $newStatus,
                'status_message' => $result['message'],
                'last_checked_at' => now(),
            ]);

            Log::info("Container action completed", [
                'site_id' => $this->site->id,
                'action' => $this->action,
                'status' => $newStatus,
            ]);

            $ssh->disconnect();

        } catch (Exception $e) {
            Log::error("Failed to manage container", [
                'site_id' => $this->site->id,
                'action' => $this->action,
                'error' => $e->getMessage(),
            ]);

            $this->site->update([
                'status' => 'failed',
                'status_message' => "Action '{$this->action}' failed: " . $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        $this->site->update([
            'status' => 'failed',
            'status_message' => "Action '{$this->action}' failed: " . $exception->getMessage(),
        ]);
    }
}