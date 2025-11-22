<?php

namespace App\Services;

use App\Models\WordPressSite;
use Exception;
use Illuminate\Support\Str;

class DockerServices
{
    protected SSHService $ssh;

    public function __construct(SSHService $ssh)
    {
        $this->ssh = $ssh;
    }

    /**
     * Generate docker-compose.yml content
     */
    public function generateDockerCompose(WordPressSite $site): string
    {
        $containerName = $site->container_name;
        $dbName = $site->db_name;
        $dbUser = $site->db_user;
        $dbPassword = $site->db_password;
        $wpVersion = $site->wp_version;
        $containerPort = $site->container_port;

        return <<<YAML
version: '3.8'

services:
  wordpress:
    image: wordpress:{$wpVersion}
    container_name: {$containerName}
    restart: unless-stopped
    ports:
      - "{$containerPort}:80"
    environment:
      WORDPRESS_DB_HOST: db:3306
      WORDPRESS_DB_NAME: {$dbName}
      WORDPRESS_DB_USER: {$dbUser}
      WORDPRESS_DB_PASSWORD: {$dbPassword}
    volumes:
      - wordpress_data:/var/www/html
    depends_on:
      - db
    networks:
      - wp_network

  db:
    image: mysql:8.0
    container_name: {$containerName}_db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: {$dbName}
      MYSQL_USER: {$dbUser}
      MYSQL_PASSWORD: {$dbPassword}
      MYSQL_ROOT_PASSWORD: {$dbPassword}_root
    volumes:
      - db_data:/var/lib/mysql
    networks:
      - wp_network

volumes:
  wordpress_data:
  db_data:

networks:
  wp_network:
    driver: bridge
YAML;
    }

    /**
     * Generate .env file content
     */
    public function generateEnvFile(WordPressSite $site): string
    {
        return <<<ENV
# WordPress Configuration
WORDPRESS_DB_HOST=db:3306
WORDPRESS_DB_NAME={$site->db_name}
WORDPRESS_DB_USER={$site->db_user}
WORDPRESS_DB_PASSWORD={$site->db_password}

# Site Configuration
SITE_URL={$site->url}
SITE_DOMAIN={$site->domain}
CONTAINER_PORT={$site->container_port}
ENV;
    }

    /**
     * Deploy a WordPress site
     */
    public function deploy(WordPressSite $site): array
    {
        $workDir = "/opt/wordpress/{$site->container_name}";
        
        try {
            // Create working directory
            $this->ssh->execute("mkdir -p {$workDir}");
            
            // Generate and upload docker-compose.yml
            $dockerCompose = $this->generateDockerCompose($site);
            $this->ssh->createFile("{$workDir}/docker-compose.yml", $dockerCompose);
            
            // Generate and upload .env file
            $envFile = $this->generateEnvFile($site);
            $this->ssh->createFile("{$workDir}/.env", $envFile);
            
            // Stop any existing container
            $this->ssh->execute("cd {$workDir} && docker-compose down 2>/dev/null || true");
            
            // Start the containers
            $result = $this->ssh->execute("cd {$workDir} && docker-compose up -d");
            
            if (!$result['success']) {
                throw new Exception("Failed to start containers: " . $result['output']);
            }
            
            // Wait for containers to be healthy
            sleep(5);
            
            // Check container status
            $statusResult = $this->getContainerStatus($site->container_name);
            
            return [
                'success' => true,
                'message' => 'WordPress site deployed successfully',
                'status' => $statusResult,
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Start a WordPress container
     */
    public function start(WordPressSite $site): array
    {
        $workDir = "/opt/wordpress/{$site->container_name}";
        
        try {
            $result = $this->ssh->execute("cd {$workDir} && docker-compose start");
            
            return [
                'success' => $result['success'],
                'message' => $result['success'] ? 'Container started successfully' : 'Failed to start container',
                'output' => $result['output'],
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Stop a WordPress container
     */
    public function stop(WordPressSite $site): array
    {
        $workDir = "/opt/wordpress/{$site->container_name}";
        
        try {
            $result = $this->ssh->execute("cd {$workDir} && docker-compose stop");
            
            return [
                'success' => $result['success'],
                'message' => $result['success'] ? 'Container stopped successfully' : 'Failed to stop container',
                'output' => $result['output'],
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Remove a WordPress container and its data
     */
    public function remove(WordPressSite $site, bool $removeVolumes = false): array
    {
        $workDir = "/opt/wordpress/{$site->container_name}";
        
        try {
            $volumeFlag = $removeVolumes ? '-v' : '';
            $result = $this->ssh->execute("cd {$workDir} && docker-compose down {$volumeFlag}");
            
            // Remove the working directory
            $this->ssh->execute("rm -rf {$workDir}");
            
            return [
                'success' => $result['success'],
                'message' => $result['success'] ? 'Container removed successfully' : 'Failed to remove container',
                'output' => $result['output'],
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get container status
     */
    public function getContainerStatus(string $containerName): array
    {
        $result = $this->ssh->execute("docker ps -a --filter name={$containerName} --format '{{.Status}}'");
        
        if (!$result['success'] || empty(trim($result['output']))) {
            return [
                'exists' => false,
                'status' => 'not_found',
                'message' => 'Container not found',
            ];
        }
        
        $status = trim($result['output']);
        $isRunning = str_contains(strtolower($status), 'up');
        
        return [
            'exists' => true,
            'status' => $isRunning ? 'running' : 'stopped',
            'message' => $status,
        ];
    }

    /**
     * Restart a WordPress container
     */
    public function restart(WordPressSite $site): array
    {
        $workDir = "/opt/wordpress/{$site->container_name}";
        
        try {
            $result = $this->ssh->execute("cd {$workDir} && docker-compose restart");
            
            return [
                'success' => $result['success'],
                'message' => $result['success'] ? 'Container restarted successfully' : 'Failed to restart container',
                'output' => $result['output'],
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get container logs
     */
    public function getLogs(WordPressSite $site, int $lines = 100): array
    {
        $workDir = "/opt/wordpress/{$site->container_name}";
        
        try {
            $result = $this->ssh->execute("cd {$workDir} && docker-compose logs --tail={$lines}");
            
            return [
                'success' => $result['success'],
                'logs' => $result['output'],
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}