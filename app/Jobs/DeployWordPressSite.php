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
        Log::info("Starting remote deployment for site: {$site->site_name}");
        
        $ssh = new SSHService($site->server);
        
        try {
            Log::info("Connecting to remote server...");
            $ssh->connect();
            Log::info("Connected successfully to remote server");

            // Find Docker path - try multiple common locations
            $findDockerCmd = "which docker 2>/dev/null || command -v docker 2>/dev/null || " .
                           "test -f /usr/bin/docker && echo '/usr/bin/docker' || " .
                           "test -f /usr/local/bin/docker && echo '/usr/local/bin/docker' || " .
                           "test -f /snap/bin/docker && echo '/snap/bin/docker' || " .
                           "echo 'not_found'";
            
            $dockerPathResult = $ssh->execute($findDockerCmd);
            $dockerPath = trim($dockerPathResult['output']);
            
            if ($dockerPath === 'not_found' || empty($dockerPath)) {
                throw new \Exception("Docker is not installed on the remote server. Please install Docker first.");
            }
            
            Log::info("Docker path: {$dockerPath}");
            
            // Verify Docker is accessible
            $dockerVersionResult = $ssh->execute("{$dockerPath} --version 2>&1");
            if (!$dockerVersionResult['success']) {
                throw new \Exception("Docker is not installed or not accessible on the remote server: " . $dockerVersionResult['output']);
            }
            Log::info("Docker version: " . trim($dockerVersionResult['output']));

            // Use home directory if /var/www is not accessible, otherwise use /var/www
            $basePathResult = $ssh->execute("test -w /var/www && echo '/var/www' || echo \"\$HOME\"");
            $basePath = trim($basePathResult['output']) ?: "\$HOME";
            
            $remotePath = "{$basePath}/wordpress-sites/{$site->container_name}";
            Log::info("Using base path: {$basePath}");
            Log::info("Creating remote directory: {$remotePath}");
            
            // Create remote directory with explicit error handling
            try {
                $ssh->createDirectory($remotePath);
                Log::info("Remote directory created successfully");
            } catch (\Exception $e) {
                Log::error("Failed to create remote directory: " . $e->getMessage());
                throw new \Exception("Failed to create remote directory {$remotePath}: " . $e->getMessage());
            }
            
            // Verify directory exists
            $verifyResult = $ssh->execute("test -d {$remotePath} && echo 'exists' || echo 'not found'");
            Log::info("Directory verification: " . $verifyResult['output']);
            
            if (strpos($verifyResult['output'], 'not found') !== false) {
                throw new \Exception("Directory {$remotePath} was not created successfully");
            }

            // Generate and upload docker-compose
            Log::info("Generating docker-compose.yml");
            $dockerCompose = $this->generateDockerCompose($site);
            $tempFile = sys_get_temp_dir() . '/docker-compose-' . $site->container_name . '.yml';
            file_put_contents($tempFile, $dockerCompose);
            
            Log::info("Uploading docker-compose.yml to remote server");
            $uploadSuccess = $ssh->uploadFile($tempFile, "{$remotePath}/docker-compose.yml");
            unlink($tempFile);
            
            if (!$uploadSuccess) {
                throw new \Exception("Failed to upload docker-compose.yml");
            }
            Log::info("docker-compose.yml uploaded successfully");
            
            // Verify file was uploaded
            $verifyFileResult = $ssh->execute("test -f {$remotePath}/docker-compose.yml && echo 'exists' || echo 'not found'");
            Log::info("File verification: " . $verifyFileResult['output']);

            // Start containers using full docker path
            Log::info("Starting Docker containers on remote server");
            $result = $ssh->execute("cd {$remotePath} && {$dockerPath} compose up -d 2>&1");
            
            Log::info("Docker compose output: " . $result['output']);
            
            if (!$result['success'] || strpos($result['output'], 'error') !== false || strpos($result['output'], 'Error') !== false) {
                throw new \Exception("Failed to start remote containers: " . $result['output']);
            }
            
            Log::info("Docker containers started successfully");

            // Wait for WordPress
            $this->waitForRemoteWordPress($site, $ssh, $dockerPath);

            // Auto-install WordPress
            Log::info("Starting WordPress installation");
            $installer = new WordPressInstallerService($ssh);
            $installer->autoInstallWordPress($site);
            Log::info("WordPress installation completed");

        } catch (\Exception $e) {
            Log::error("Remote deployment error: " . $e->getMessage());
            throw $e;
        } finally {
            $ssh->disconnect();
            Log::info("SSH connection closed");
        }
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

    private function waitForRemoteWordPress(WordPressSite $site, SSHService $ssh, string $dockerPath = 'docker', int $maxAttempts = 30): void
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $result = $ssh->execute("{$dockerPath} inspect -f '{{.State.Running}}' {$site->container_name}");
            
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