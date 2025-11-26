<?php

namespace App\Jobs;

use App\Models\WordPressSite;
use App\Services\DockerService;
use App\Services\SSHService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateWordPressSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 3;
    public $backoff = 30;

    protected $siteId;
    protected $updateData;
    protected $needsRecreation;

    public function __construct(int $siteId, array $updateData, bool $needsRecreation)
    {
        $this->siteId = $siteId;
        $this->updateData = $updateData;
        $this->needsRecreation = $needsRecreation;
    }

    public function handle(): void
    {
        $site = WordPressSite::find($this->siteId);
        
        if (!$site) {
            Log::error("WordPress site not found for update: {$this->siteId}");
            return;
        }

        try {
            Log::info("Starting update for site: {$site->site_name}");
            
            if ($this->needsRecreation) {
                $site->update(['status' => 'deploying']);
                
                // Stop the site first
                $dockerService = new DockerService();
                
                if ($site->is_remote && $site->server) {
                    $dockerService->stopRemoteSite($site);
                } else {
                    $dockerService->stopLocalSite($site);
                }

                // Update site data
                $site->update($this->updateData);

                // Recreate containers
                if ($site->is_remote && $site->server) {
                    $this->recreateRemoteSite($site, $dockerService);
                } else {
                    $this->recreateLocalSite($site, $dockerService);
                }

                $site->update([
                    'status' => 'running',
                    'error_message' => null
                ]);
            } else {
                // Simple update without recreation
                $site->update($this->updateData);
            }
            
            Log::info("Successfully updated site: {$site->site_name}");
        } catch (\Exception $e) {
            Log::error("Failed to update site {$site->site_name}: " . $e->getMessage());
            
            $site->update([
                'status' => 'error',
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function recreateLocalSite(WordPressSite $site, DockerService $dockerService): void
    {
        $sitePath = storage_path('wordpress-sites') . DIRECTORY_SEPARATOR . $site->container_name;
        
        // Remove old containers
        $process = new \Symfony\Component\Process\Process(
            ['docker', 'compose', 'down'],
            $sitePath
        );
        $process->run();

        // Regenerate docker-compose
        $dockerCompose = $this->generateDockerCompose($site);
        file_put_contents($sitePath . DIRECTORY_SEPARATOR . 'docker-compose.yml', $dockerCompose);

        // Start with new config
        $process = new \Symfony\Component\Process\Process(
            ['docker', 'compose', 'up', '-d'],
            $sitePath
        );
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception("Failed to recreate containers: " . $process->getErrorOutput());
        }
    }

    private function recreateRemoteSite(WordPressSite $site, DockerService $dockerService): void
    {
        $ssh = new SSHService($site->server);
        $ssh->connect();

        $remotePath = "/var/www/wordpress-sites/{$site->container_name}";
        
        // Stop containers
        $ssh->execute("cd {$remotePath} && docker compose down");

        // Update docker-compose
        $dockerCompose = $this->generateDockerCompose($site);
        $tempFile = sys_get_temp_dir() . '/docker-compose-' . $site->container_name . '.yml';
        file_put_contents($tempFile, $dockerCompose);
        
        $ssh->uploadFile($tempFile, "{$remotePath}/docker-compose.yml");
        unlink($tempFile);

        // Start with new config
        $result = $ssh->execute("cd {$remotePath} && docker compose up -d");
        
        if (!$result['success']) {
            throw new \Exception("Failed to recreate remote containers: " . $result['output']);
        }

        $ssh->disconnect();
    }

    private function generateDockerCompose(WordPressSite $site): string
    {
        $url = $site->is_remote 
            ? "http://{$site->domain}:{$site->port}" 
            : "http://localhost:{$site->port}";

        return <<<YAML
version: '3.8'

services:
  db:
    image: mysql:8.0
    container_name: {$site->container_name}_db
    volumes:
      - db_data:/var/lib/mysql
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: {$site->db_root_password}
      MYSQL_DATABASE: {$site->db_name}
      MYSQL_USER: {$site->db_user}
      MYSQL_PASSWORD: {$site->db_password}
    networks:
      - {$site->container_name}_network
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      timeout: 20s
      retries: 10

  wordpress:
    depends_on:
      db:
        condition: service_healthy
    image: wordpress:latest
    container_name: {$site->container_name}
    ports:
      - "{$site->port}:80"
    restart: always
    environment:
      WORDPRESS_DB_HOST: db:3306
      WORDPRESS_DB_USER: {$site->db_user}
      WORDPRESS_DB_PASSWORD: {$site->db_password}
      WORDPRESS_DB_NAME: {$site->db_name}
    volumes:
      - wordpress_data:/var/www/html
    networks:
      - {$site->container_name}_network

volumes:
  db_data:
  wordpress_data:

networks:
  {$site->container_name}_network:
    driver: bridge
YAML;
    }

    public function failed(\Throwable $exception): void
    {
        $site = WordPressSite::find($this->siteId);
        
        if ($site) {
            $site->update([
                'status' => 'error',
                'error_message' => 'Update failed: ' . $exception->getMessage()
            ]);
        }

        Log::error("Update job failed for site ID {$this->siteId}: " . $exception->getMessage());
    }
}