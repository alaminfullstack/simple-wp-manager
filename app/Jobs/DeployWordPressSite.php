<?php

namespace App\Jobs;

use App\Models\WordPressSite;
use App\Models\Server;
use App\Services\DockerService;
use App\Services\SSHService;
use App\Services\WordPressInstallerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeployWordPressSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 3;
    public $backoff = 30; // Wait 30 seconds between retries

    protected $siteId;

    public function __construct(int $siteId, array $siteData = [])
    {
        $this->siteId = $siteId;
        // Note: Site data already in database, no need to pass it
    }

    public function handle(): void
    {
        $site = WordPressSite::find($this->siteId);
        
        if (!$site) {
            Log::error("WordPress site not found: {$this->siteId}");
            return;
        }

        try {
            Log::info("Starting deployment for site: {$site->site_name} (ID: {$site->id})");
            
            $site->update(['status' => 'deploying']);

            $dockerService = new DockerService();

            if ($site->is_remote && $site->server) {
                $this->deployRemoteSite($site, $dockerService);
            } else {
                $this->deployLocalSite($site, $dockerService);
            }

            $site->update([
                'status' => 'running',
                'error_message' => null
            ]);
            
            Log::info("Successfully deployed site: {$site->site_name}");
        } catch (\Exception $e) {
            Log::error("Failed to deploy site {$site->site_name}: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            
            $site->update([
                'status' => 'error',
                'error_message' => $e->getMessage()
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    private function deployLocalSite(WordPressSite $site, DockerService $dockerService): void
    {
        $sitesPath = storage_path('wordpress-sites');
        $sitePath = $sitesPath . DIRECTORY_SEPARATOR . $site->container_name;

        // Create directory
        if (!file_exists($sitePath)) {
            mkdir($sitePath, 0755, true);
        }

        // Generate docker-compose.yml
        $dockerCompose = $this->generateDockerCompose($site);
        file_put_contents($sitePath . DIRECTORY_SEPARATOR . 'docker-compose.yml', $dockerCompose);

        // Start containers
        $process = new \Symfony\Component\Process\Process(
            ['docker', 'compose', 'up', '-d'],
            $sitePath
        );
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \Exception("Failed to start containers: " . $process->getErrorOutput());
        }

        // Wait for WordPress
        $this->waitForWordPress($site);

        // Auto-install WordPress
        $installer = new WordPressInstallerService();
        $installer->autoInstallWordPress($site);
    }

    private function deployRemoteSite(WordPressSite $site, DockerService $dockerService): void
    {
        $ssh = new SSHService($site->server);
        $ssh->connect();

        $remotePath = "/var/www/wordpress-sites/{$site->container_name}";
        
        // Create remote directory
        $ssh->createDirectory($remotePath);

        // Generate and upload docker-compose
        $dockerCompose = $this->generateDockerCompose($site);
        $tempFile = sys_get_temp_dir() . '/docker-compose-' . $site->container_name . '.yml';
        file_put_contents($tempFile, $dockerCompose);
        
        $ssh->uploadFile($tempFile, "{$remotePath}/docker-compose.yml");
        unlink($tempFile);

        // Start containers
        $result = $ssh->execute("cd {$remotePath} && docker compose up -d");
        
        if (!$result['success']) {
            throw new \Exception("Failed to start remote containers: " . $result['output']);
        }

        // Wait for WordPress
        $this->waitForRemoteWordPress($site, $ssh);

        // Auto-install WordPress
        $installer = new WordPressInstallerService($ssh);
        $installer->autoInstallWordPress($site);

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

    private function waitForWordPress(WordPressSite $site, int $maxAttempts = 30): void
    {
        $url = "http://localhost:{$site->port}";
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            try {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 3,
                        'ignore_errors' => true
                    ]
                ]);
                
                $headers = @get_headers($url, 1, $context);
                
                if ($headers && (strpos($headers[0], '200') !== false || strpos($headers[0], '302') !== false)) {
                    sleep(5); // Extra time for stability
                    Log::info("WordPress is ready at {$url}");
                    return;
                }
            } catch (\Exception $e) {
                Log::debug("Attempt {$attempts}: Waiting for WordPress at {$url}");
            }
            
            sleep(3);
            $attempts++;
        }

        throw new \Exception("WordPress failed to start within timeout period");
    }

    private function waitForRemoteWordPress(WordPressSite $site, SSHService $ssh, int $maxAttempts = 30): void
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $result = $ssh->execute("docker inspect -f '{{.State.Running}}' {$site->container_name}");
            
            if ($result['success'] && trim($result['output']) === 'true') {
                sleep(5); // Extra time for stability
                Log::info("Remote WordPress container is running");
                return;
            }
            
            Log::debug("Attempt {$attempts}: Waiting for remote container");
            sleep(3);
            $attempts++;
        }

        throw new \Exception("Remote WordPress failed to start within timeout period");
    }

    public function failed(\Throwable $exception): void
    {
        $site = WordPressSite::find($this->siteId);
        
        if ($site) {
            $site->update([
                'status' => 'error',
                'error_message' => 'Deployment failed after ' . $this->tries . ' attempts: ' . $exception->getMessage()
            ]);
        }

        Log::error("Deployment job permanently failed for site ID {$this->siteId}: " . $exception->getMessage());
    }
}