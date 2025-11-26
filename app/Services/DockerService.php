<?php

namespace App\Services;

use App\Models\WordPressSite;
use App\Models\Server;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DockerService
{
    private $sitesPath;
    private $sshService;

    public function __construct(SSHService $sshService = null)
    {
        $this->sitesPath = storage_path('wordpress-sites');
        $this->sshService = $sshService;
        
        if (!file_exists($this->sitesPath)) {
            mkdir($this->sitesPath, 0755, true);
        }
    }

    public function createWordPressSite(array $data): WordPressSite
    {
        $containerName = 'wp_' . uniqid();
        $server = isset($data['server_id']) ? Server::find($data['server_id']) : null;
        $isRemote = $server && !$server->isLocal();
        $port = $this->findAvailablePort($data['port'] ?? 8080, $server);

        $site = WordPressSite::create([
            'server_id' => $data['server_id'] ?? null,
            'is_remote' => $isRemote,
            'site_name' => $data['site_name'],
            'domain' => $data['domain'] ?? ($isRemote ? $server->ip_address : 'localhost'),
            'port' => $port,
            'container_name' => $containerName,
            'db_name' => $data['db_name'] ?? 'wordpress',
            'db_user' => $data['db_user'] ?? 'wpuser',
            'db_password' => $data['db_password'] ?? $this->generatePassword(),
            'db_root_password' => $this->generatePassword(),
            'admin_email' => $data['admin_email'] ?? 'admin@gmail.com',
            'admin_user' => $data['admin_user'] ?? 'admin',
            'admin_password' => $data['admin_password'] ?? $this->generatePassword(),
            'status' => 'deploying',
        ]);

        try {
            if ($isRemote) {
                $this->createRemoteSite($site, $server);
            } else {
                $this->createLocalSite($site);
            }
            
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

    private function createLocalSite(WordPressSite $site): void
    {
        $this->createDockerCompose($site);
        $this->startContainers($site);
        $this->waitForWordPress($site);
        
        // Auto-install WordPress using WP-CLI
        $installer = new \App\Services\WordPressInstallerService();
        $installed = $installer->autoInstallWordPress($site);
        
        if ($installed) {
            \Log::info("WordPress auto-installed for site: {$site->site_name}");
        } else {
            \Log::warning("WordPress auto-install failed for site: {$site->site_name}. User will need to complete setup manually.");
        }
    }

    private function createRemoteSite(WordPressSite $site, Server $server): void
    {
        $ssh = new SSHService($server);
        $ssh->connect();

        $remotePath = "/var/www/wordpress-sites/{$site->container_name}";
        $ssh->createDirectory($remotePath);

        $dockerCompose = $this->generateDockerComposeContent($site);
        $tempFile = sys_get_temp_dir() . '/docker-compose-' . $site->container_name . '.yml';
        file_put_contents($tempFile, $dockerCompose);

        $ssh->uploadFile($tempFile, "{$remotePath}/docker-compose.yml");
        unlink($tempFile);

        $result = $ssh->execute("cd {$remotePath} && docker compose up -d");
        
        if (!$result['success']) {
            throw new \Exception("Failed to start remote containers: " . $result['output']);
        }

        $this->waitForRemoteWordPress($site, $ssh);
        
        // Auto-install WordPress using WP-CLI
        $installer = new \App\Services\WordPressInstallerService(new SSHService($server));
        $installed = $installer->autoInstallWordPress($site);
        
        if ($installed) {
            \Log::info("Remote WordPress auto-installed for site: {$site->site_name}");
        } else {
            \Log::warning("Remote WordPress auto-install failed for site: {$site->site_name}. User will need to complete setup manually.");
        }
        
        $ssh->disconnect();
    }

    public function stopSite(WordPressSite $site): void
    {
        if ($site->is_remote && $site->server) {
            $this->stopRemoteSite($site);
        } else {
            $this->stopLocalSite($site);
        }
    }

    private function stopLocalSite(WordPressSite $site): void
    {
        $sitePath = $this->sitesPath . DIRECTORY_SEPARATOR . $site->container_name;
        
        $process = new Process(['docker', 'compose', 'stop'], $sitePath);
        $process->run();

        if (!$process->isSuccessful()) {
            $process = new Process(['docker-compose', 'stop'], $sitePath);
            $process->run();
        }

        if ($process->isSuccessful()) {
            $site->update(['status' => 'stopped']);
        }
    }

    private function stopRemoteSite(WordPressSite $site): void
    {
        $ssh = new SSHService($site->server);
        $ssh->connect();

        $remotePath = "/var/www/wordpress-sites/{$site->container_name}";
        $result = $ssh->execute("cd {$remotePath} && docker compose stop");
        
        if ($result['success']) {
            $site->update(['status' => 'stopped']);
        }
        
        $ssh->disconnect();
    }

    public function startSite(WordPressSite $site): void
    {
        if ($site->is_remote && $site->server) {
            $this->startRemoteSite($site);
        } else {
            $this->startLocalSite($site);
        }
    }

    private function startLocalSite(WordPressSite $site): void
    {
        $sitePath = $this->sitesPath . DIRECTORY_SEPARATOR . $site->container_name;
        
        $process = new Process(['docker', 'compose', 'start'], $sitePath);
        $process->run();

        if (!$process->isSuccessful()) {
            $process = new Process(['docker-compose', 'start'], $sitePath);
            $process->run();
        }

        if ($process->isSuccessful()) {
            $site->update(['status' => 'running']);
        }
    }

    private function startRemoteSite(WordPressSite $site): void
    {
        $ssh = new SSHService($site->server);
        $ssh->connect();

        $remotePath = "/var/www/wordpress-sites/{$site->container_name}";
        $result = $ssh->execute("cd {$remotePath} && docker compose start");
        
        if ($result['success']) {
            $site->update(['status' => 'running']);
        }
        
        $ssh->disconnect();
    }

    public function deleteSite(WordPressSite $site): void
    {
        \Log::info("Delete Request {$site}");
        if ($site->is_remote && $site->server) {
            $this->deleteRemoteSite($site);
        } else {
            $this->deleteLocalSite($site);
        }
    }

    private function deleteLocalSite(WordPressSite $site): void
    {
        $sitePath = $this->sitesPath . DIRECTORY_SEPARATOR . $site->container_name;
        
        try {
            // Stop and remove containers with volumes
            if (file_exists($sitePath)) {
                $process = new Process(['docker', 'compose', 'down', '-v'], $sitePath);
                $process->setTimeout(120);
                $process->run();

                if (!$process->isSuccessful()) {
                    // Try old syntax
                    $process = new Process(['docker-compose', 'down', '-v'], $sitePath);
                    $process->setTimeout(120);
                    $process->run();
                }

                // Force remove containers if still exist
                if($site->container_name != null){
                    $this->forceRemoveContainer($site->container_name);
                    $this->forceRemoveContainer($site->container_name . '_db');
                    // $this->forceRemoveContainer($site->container_name . '_cli');
                }
               

                // Wait a bit for cleanup
                sleep(2);

                // Remove directory
                $this->deleteDirectory($sitePath);
                
                \Log::info("Successfully deleted local site directory: {$sitePath} {$site}");

            }
        } catch (\Exception $e) {
            \Log::error("Error deleting local site: " . $e->getMessage());
            // Don't throw - we still want to delete the database record
        }

        $site->delete();
    }

    private function deleteRemoteSite(WordPressSite $site): void
    {
        try {
            $ssh = new SSHService($site->server);
            $ssh->connect();

            $remotePath = "/var/www/wordpress-sites/{$site->container_name}";
            
            // Stop and remove containers
            $ssh->execute("cd {$remotePath} && docker compose down -v 2>/dev/null || docker-compose down -v 2>/dev/null || true");
            
            // Force remove containers
            $ssh->execute("docker rm -f {$site->container_name} 2>/dev/null || true");
            $ssh->execute("docker rm -f {$site->container_name}_db 2>/dev/null || true");
            // $ssh->execute("docker rm -f {$site->container_name}_cli 2>/dev/null || true");
            
            // Wait for cleanup
            sleep(2);
            
            // Remove directory
            $ssh->execute("rm -rf {$remotePath}");
            
            $ssh->disconnect();
            
            \Log::info("Successfully deleted remote site: {$remotePath}");
        } catch (\Exception $e) {
            \Log::error("Error deleting remote site: " . $e->getMessage());
            // Don't throw - we still want to delete the database record
        }
        
        $site->delete();
    }

    private function forceRemoveContainer(string $containerName): void
    {
        try {
            $process = new Process(['docker', 'rm', '-f', $containerName]);
            $process->run();
            
            if ($process->isSuccessful()) {
                \Log::info("Force removed container: {$containerName}");
            }
        } catch (\Exception $e) {
            // Ignore errors - container might not exist
        }
    }

    public function getSiteLogs(WordPressSite $site, int $lines = 100): string
    {
        if ($site->is_remote && $site->server) {
            return $this->getRemoteSiteLogs($site, $lines);
        }
        
        $process = new Process(['docker', 'logs', '--tail', (string)$lines, $site->container_name]);
        $process->run();

        return $process->getOutput() ?: 'No logs available';
    }

    private function getRemoteSiteLogs(WordPressSite $site, int $lines): string
    {
        $ssh = new SSHService($site->server);
        $ssh->connect();

        $result = $ssh->execute("docker logs --tail {$lines} {$site->container_name}");
        $ssh->disconnect();

        return $result['output'] ?: 'No logs available';
    }

    public function updateWordPressSite(WordPressSite $site, array $data): WordPressSite
    {
        $needsRecreation = 
            (isset($data['port']) && $data['port'] != $site->port) ||
            (isset($data['db_name']) && $data['db_name'] != $site->db_name) ||
            (isset($data['db_user']) && $data['db_user'] != $site->db_user) ||
            (isset($data['db_password']) && $data['db_password'] != $site->db_password);

        try {
            if ($needsRecreation) {
                $this->stopSite($site);
                
                $site->update([
                    'site_name' => $data['site_name'] ?? $site->site_name,
                    'domain' => $data['domain'] ?? $site->domain,
                    'port' => $data['port'] ?? $site->port,
                    'db_name' => $data['db_name'] ?? $site->db_name,
                    'db_user' => $data['db_user'] ?? $site->db_user,
                    'db_password' => $data['db_password'] ?? $site->db_password,
                    'admin_email' => $data['admin_email'] ?? $site->admin_email,
                    'admin_user' => $data['admin_user'] ?? $site->admin_user,
                    'admin_password' => $data['admin_password'] ?? $site->admin_password,
                    'status' => 'deploying',
                ]);

                if ($site->is_remote && $site->server) {
                    $this->recreateRemoteSite($site);
                } else {
                    $this->recreateLocalSite($site);
                }
                
                $site->update(['status' => 'running']);
            } else {
                $site->update([
                    'site_name' => $data['site_name'] ?? $site->site_name,
                    'domain' => $data['domain'] ?? $site->domain,
                    'admin_email' => $data['admin_email'] ?? $site->admin_email,
                    'admin_user' => $data['admin_user'] ?? $site->admin_user,
                    'admin_password' => $data['admin_password'] ?? $site->admin_password,
                ]);
            }
        } catch (\Exception $e) {
            $site->update([
                'status' => 'error',
                'error_message' => $e->getMessage()
            ]);
            throw $e;
        }

        return $site->fresh();
    }

    private function recreateLocalSite(WordPressSite $site): void
    {
        $sitePath = $this->sitesPath . DIRECTORY_SEPARATOR . $site->container_name;
        
        $process = new Process(['docker', 'compose', 'down'], $sitePath);
        $process->run();
        
        $this->createDockerCompose($site);
        $this->startContainers($site);
        $this->waitForWordPress($site);
    }

    private function recreateRemoteSite(WordPressSite $site): void
    {
        $ssh = new SSHService($site->server);
        $ssh->connect();

        $remotePath = "/var/www/wordpress-sites/{$site->container_name}";
        $ssh->execute("cd {$remotePath} && docker compose down");

        $dockerCompose = $this->generateDockerComposeContent($site);
        $tempFile = sys_get_temp_dir() . '/docker-compose-' . $site->container_name . '.yml';
        file_put_contents($tempFile, $dockerCompose);

        $ssh->uploadFile($tempFile, "{$remotePath}/docker-compose.yml");
        unlink($tempFile);

        $result = $ssh->execute("cd {$remotePath} && docker compose up -d");
        
        if (!$result['success']) {
            throw new \Exception("Failed to start remote containers: " . $result['output']);
        }

        $this->waitForRemoteWordPress($site, $ssh);
        $ssh->disconnect();
    }

    private function createDockerCompose(WordPressSite $site): void
    {
        $sitePath = $this->sitesPath . DIRECTORY_SEPARATOR . $site->container_name;
        
        if (!file_exists($sitePath)) {
            mkdir($sitePath, 0755, true);
        }

        $dockerCompose = $this->generateDockerComposeContent($site);
        $composePath = $sitePath . DIRECTORY_SEPARATOR . 'docker-compose.yml';
        file_put_contents($composePath, $dockerCompose);
    }

    private function generateDockerComposeContent(WordPressSite $site): string
    {
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

    private function startContainers(WordPressSite $site): void
    {
        $sitePath = $this->sitesPath . DIRECTORY_SEPARATOR . $site->container_name;
        
        $process = new Process(['docker', 'compose', 'up', '-d'], $sitePath);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
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
                    sleep(3);
                    \Log::info("WordPress site {$site->site_name} is ready at {$url}");
                    return;
                }
            } catch (\Exception $e) {
                \Log::warning("Attempt {$attempts}: Waiting for WordPress at {$url} - " . $e->getMessage());
            }
            
            sleep(2);
            $attempts++;
        }

        $error = "WordPress site failed to start within timeout period ({$maxAttempts} attempts)";
        \Log::error($error . " for site: {$site->site_name}");
        throw new \Exception($error);
    }

    private function waitForRemoteWordPress(WordPressSite $site, SSHService $ssh, int $maxAttempts = 30): void
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $result = $ssh->execute("docker inspect -f '{{.State.Running}}' {$site->container_name}");
            
            if ($result['success'] && trim($result['output']) === 'true') {
                sleep(3);
                \Log::info("Remote WordPress site {$site->site_name} is running");
                return;
            }
            
            \Log::warning("Attempt {$attempts}: Waiting for remote WordPress container {$site->container_name}");
            sleep(2);
            $attempts++;
        }

        $error = "WordPress site failed to start within timeout period ({$maxAttempts} attempts)";
        \Log::error($error . " for remote site: {$site->site_name}");
        throw new \Exception($error);
    }

    public function checkDockerAvailability(): array
    {
        $process = new Process(['docker', 'info']);
        $process->run();

        $dockerRunning = $process->isSuccessful();

        $process = new Process(['docker', 'compose', 'version']);
        $process->run();
        
        $composeAvailable = $process->isSuccessful();
        
        if (!$composeAvailable) {
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

    public function findAvailablePort(int $startPort = 8080, ?Server $server = null): int
    {
        $port = $startPort;
        $maxPort = 9000;
        
        while ($port < $maxPort) {
            $query = WordPressSite::where('port', $port);
            
            if ($server) {
                $query->where('server_id', $server->id);
            } else {
                $query->whereNull('server_id');
            }
            
            if (!$query->exists()) {
                return $port;
            }
            $port++;
        }

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