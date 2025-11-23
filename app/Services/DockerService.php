<?php

namespace App\Services;

use App\Models\WordPressSite;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DockerService
{
    private $sitesPath;

    public function __construct()
    {
        $this->sitesPath = storage_path('wordpress-sites');
        if (!file_exists($this->sitesPath)) {
            mkdir($this->sitesPath, 0755, true);
        }
    }

    public function createWordPressSite(array $data): WordPressSite
    {
        // Generate unique identifiers
        $containerName = 'wp_' . uniqid();
        $dbContainerName = 'wpdb_' . uniqid();
        $port = $this->findAvailablePort($data['port'] ?? 8080);

        // Create site record
        $site = WordPressSite::create([
            'site_name' => $data['site_name'],
            'domain' => $data['domain'] ?? 'localhost',
            'port' => $port,
            'container_name' => $containerName,
            'db_name' => $data['db_name'] ?? 'wordpress',
            'db_user' => $data['db_user'] ?? 'wpuser',
            'db_password' => $data['db_password'] ?? $this->generatePassword(),
            'db_root_password' => $this->generatePassword(),
            'admin_email' => $data['admin_email'],
            'admin_user' => $data['admin_user'] ?? 'admin',
            'admin_password' => $data['admin_password'] ?? $this->generatePassword(),
            'status' => 'creating',
        ]);

        try {
            $this->createDockerCompose($site);
            $this->startContainers($site);
            $this->waitForWordPress($site);
            
            $site->update(['status' => 'running']);
        } catch (\Exception $e) {
            $site->update([
                'status' => 'error',
                'error_message' => $e->getMessage()
            ]);
            throw $e;
        }

        return $site;
    }

    private function createDockerCompose(WordPressSite $site): void
    {
        $sitePath = $this->sitesPath . DIRECTORY_SEPARATOR . $site->container_name;
        
        if (!file_exists($sitePath)) {
            mkdir($sitePath, 0755, true);
        }

        $dockerCompose = <<<YAML
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

  wordpress:
    depends_on:
      - db
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

        $composePath = $sitePath . DIRECTORY_SEPARATOR . 'docker-compose.yml';
        file_put_contents($composePath, $dockerCompose);
    }

    private function startContainers(WordPressSite $site): void
    {
        $sitePath = $this->sitesPath . DIRECTORY_SEPARATOR . $site->container_name;
        
        // Use 'docker compose' command (Docker Desktop modern syntax)
        $process = new Process(['docker', 'compose', 'up', '-d'], $sitePath);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            // Try old syntax if new one fails
            $process = new Process(['docker-compose', 'up', '-d'], $sitePath);
            $process->setTimeout(300);
            $process->run();
            
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        }
    }

    private function waitForWordPress(WordPressSite $site, int $maxAttempts = 30): void
    {
        $url = "http://localhost:{$site->port}";
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            try {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 2,
                        'ignore_errors' => true
                    ]
                ]);
                
                $headers = @get_headers($url, 1, $context);
                
                if ($headers && (strpos($headers[0], '200') !== false || strpos($headers[0], '302') !== false)) {
                    // Give it a few more seconds to fully initialize
                    sleep(3);
                    return;
                }
            } catch (\Exception $e) {
                // Continue waiting
            }
            
            sleep(2);
            $attempts++;
        }

        throw new \Exception('WordPress site failed to start within timeout period');
    }

    public function stopSite(WordPressSite $site): void
    {
        $sitePath = $this->sitesPath . DIRECTORY_SEPARATOR . $site->container_name;
        
        // Try new syntax first
        $process = new Process(['docker', 'compose', 'stop'], $sitePath);
        $process->run();

        if (!$process->isSuccessful()) {
            // Try old syntax
            $process = new Process(['docker-compose', 'stop'], $sitePath);
            $process->run();
        }

        if ($process->isSuccessful()) {
            $site->update(['status' => 'stopped']);
        }
    }

    public function startSite(WordPressSite $site): void
    {
        $sitePath = $this->sitesPath . DIRECTORY_SEPARATOR . $site->container_name;
        
        // Try new syntax first
        $process = new Process(['docker', 'compose', 'start'], $sitePath);
        $process->run();

        if (!$process->isSuccessful()) {
            // Try old syntax
            $process = new Process(['docker-compose', 'start'], $sitePath);
            $process->run();
        }

        if ($process->isSuccessful()) {
            $site->update(['status' => 'running']);
        }
    }

    public function deleteSite(WordPressSite $site): void
    {
        $sitePath = $this->sitesPath . DIRECTORY_SEPARATOR . $site->container_name;
        
        // Stop and remove containers with volumes
        $process = new Process(['docker', 'compose', 'down', '-v'], $sitePath);
        $process->run();

        if (!$process->isSuccessful()) {
            // Try old syntax
            $process = new Process(['docker-compose', 'down', '-v'], $sitePath);
            $process->run();
        }

        // Remove directory
        if (file_exists($sitePath)) {
            $this->deleteDirectory($sitePath);
        }

        $site->delete();
    }

    public function getSiteLogs(WordPressSite $site, int $lines = 100): string
    {
        $process = new Process(['docker', 'logs', '--tail', (string)$lines, $site->container_name]);
        $process->run();

        return $process->getOutput() ?: 'No logs available';
    }

    public function checkDockerAvailability(): array
    {
        // Check if Docker is running
        $process = new Process(['docker', 'info']);
        $process->run();

        $dockerRunning = $process->isSuccessful();

        // Check Docker Compose
        $process = new Process(['docker', 'compose', 'version']);
        $process->run();
        
        $composeAvailable = $process->isSuccessful();
        
        if (!$composeAvailable) {
            // Try old syntax
            $process = new Process(['docker-compose', '--version']);
            $process->run();
            $composeAvailable = $process->isSuccessful();
        }

        return [
            'docker_running' => $dockerRunning,
            'compose_available' => $composeAvailable,
            'can_create_sites' => $dockerRunning && $composeAvailable
        ];
    }

    private function findAvailablePort(int $startPort = 8080): int
    {
        $port = $startPort;
        $maxPort = 9000; // Limit search range
        
        while ($port < $maxPort) {
            if (!WordPressSite::where('port', $port)->exists()) {
                return $port;
            }
            $port++;
        }

        // If all ports in range are taken, throw exception
        throw new \Exception('No available ports found in range 8080-9000');
    }

    private function generatePassword(int $length = 16): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        $max = strlen($characters) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, $max)];
        }
        
        return $password;
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
}