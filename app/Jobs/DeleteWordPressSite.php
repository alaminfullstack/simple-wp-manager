<?php

namespace App\Jobs;

use App\Models\WordPressSite;
use App\Services\SSHService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DeleteWordPressSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;
    public $backoff = 10;

    protected $siteId;
    protected $isRemote;
    protected $serverId;
    protected $containerName;

    public function __construct(int $siteId, bool $isRemote, ?int $serverId, string $containerName)
    {
        $this->siteId = $siteId;
        $this->isRemote = $isRemote;
        $this->serverId = $serverId;
        $this->containerName = $containerName;
    }

    public function handle(): void
    {
        try {
            Log::info("Starting deletion for site ID: {$this->siteId}, Container: {$this->containerName}");
            
            if ($this->isRemote && $this->serverId) {
                $this->deleteRemoteSite();
            } else {
                $this->deleteLocalSite();
            }

            // Delete database record
            $site = WordPressSite::find($this->siteId);
            if ($site) {
                $site->delete();
            }
            
            Log::info("Successfully deleted site ID: {$this->siteId}");
        } catch (\Exception $e) {
            Log::error("Failed to delete site {$this->siteId}: " . $e->getMessage());
            throw $e;
        }
    }

    private function deleteLocalSite(): void
    {
        $sitePath = storage_path('wordpress-sites') . DIRECTORY_SEPARATOR . $this->containerName;
        
        try {
            // Stop and remove containers
            if (file_exists($sitePath)) {
                $process = new Process(['docker', 'compose', 'down', '-v'], $sitePath);
                $process->setTimeout(120);
                $process->run();

                if (!$process->isSuccessful()) {
                    $process = new Process(['docker-compose', 'down', '-v'], $sitePath);
                    $process->setTimeout(120);
                    $process->run();
                }
            }

            // Force remove containers
            $this->forceRemoveContainer($this->containerName);
            $this->forceRemoveContainer($this->containerName . '_db');
           // $this->forceRemoveContainer($this->containerName . '_cli');

            sleep(2);

            // Remove directory
            if (file_exists($sitePath)) {
                $this->deleteDirectory($sitePath);
            }
            
            Log::info("Successfully deleted local site directory: {$sitePath}");
        } catch (\Exception $e) {
            Log::error("Error deleting local site: " . $e->getMessage());
            // Don't throw - we still want to delete the database record
        }
    }

    private function deleteRemoteSite(): void
    {
        try {
            $server = \App\Models\Server::find($this->serverId);
            if (!$server) {
                throw new \Exception("Server not found");
            }

            $ssh = new SSHService($server);
            $ssh->connect();

            $remotePath = "/var/www/wordpress-sites/{$this->containerName}";
            
            // Stop and remove containers
            $ssh->execute("cd {$remotePath} && docker compose down -v 2>/dev/null || docker-compose down -v 2>/dev/null || true");
            
            // Force remove containers
            $ssh->execute("docker rm -f {$this->containerName} 2>/dev/null || true");
            $ssh->execute("docker rm -f {$this->containerName}_db 2>/dev/null || true");
           // $ssh->execute("docker rm -f {$this->containerName}_cli 2>/dev/null || true");
            
            sleep(2);
            
            // Remove directory
            $ssh->execute("rm -rf {$remotePath}");
            
            $ssh->disconnect();
            
            Log::info("Successfully deleted remote site: {$remotePath}");
        } catch (\Exception $e) {
            Log::error("Error deleting remote site: " . $e->getMessage());
            // Don't throw - we still want to delete the database record
        }
    }

    private function forceRemoveContainer(string $containerName): void
    {
        try {
            $process = new Process(['docker', 'rm', '-f', $containerName]);
            $process->run();
            
            if ($process->isSuccessful()) {
                Log::info("Force removed container: {$containerName}");
            }
        } catch (\Exception $e) {
            // Ignore errors - container might not exist
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }

        if (is_file($dir)) {
            unlink($dir);
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Delete job permanently failed for site ID {$this->siteId}: " . $exception->getMessage());
        
        // Still try to delete the database record
        $site = WordPressSite::find($this->siteId);
        if ($site) {
            $site->update([
                'status' => 'error',
                'error_message' => 'Deletion failed: ' . $exception->getMessage()
            ]);
        }
    }
}