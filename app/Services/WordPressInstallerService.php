<?php

namespace App\Services;

use App\Models\WordPressSite;
use App\Models\Server;
use Illuminate\Support\Facades\Log;

class WordPressInstallerService
{
    private $sshService;

    public function __construct(SSHService $sshService = null)
    {
        $this->sshService = $sshService;
    }

    /**
     * Auto-install WordPress using WP-CLI
     */
    public function autoInstallWordPress(WordPressSite $site): bool
    {
        try {
            if ($site->is_remote && $site->server) {
                return $this->installRemoteWordPress($site);
            } else {
                return $this->installLocalWordPress($site);
            }
        } catch (\Exception $e) {
            Log::error("WordPress auto-install failed for {$site->site_name}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Install WordPress on local Docker
     */
    private function installLocalWordPress(WordPressSite $site): bool
    {
        $maxAttempts = 20;
        $attempts = 0;

        // Wait for WordPress to be ready
        while ($attempts < $maxAttempts) {
            $process = new \Symfony\Component\Process\Process([
                'docker', 'exec', $site->container_name,
                'wp', 'core', 'is-installed', '--allow-root'
            ]);
            
            $process->run();

            if ($process->isSuccessful()) {
                Log::info("WordPress already installed for {$site->site_name}");
                return true;
            }

            // Try to install
            $installProcess = new \Symfony\Component\Process\Process([
                'docker', 'exec', $site->container_name,
                'wp', 'core', 'install',
                '--url=http://' . $site->domain . ':' . $site->port,
                '--title=' . $site->site_name,
                '--admin_user=' . $site->admin_user,
                '--admin_password=' . $site->admin_password,
                '--admin_email=' . $site->admin_email,
                '--skip-email',
                '--allow-root'
            ]);

            $installProcess->run();

            if ($installProcess->isSuccessful()) {
                Log::info("WordPress successfully installed for {$site->site_name}");
                return true;
            }

            sleep(3);
            $attempts++;
        }

        Log::warning("WordPress auto-install timed out for {$site->site_name}");
        return false;
    }

    /**
     * Install WordPress on remote server
     */
    private function installRemoteWordPress(WordPressSite $site): bool
    {
        $ssh = new SSHService($site->server);
        $ssh->connect();

        $maxAttempts = 20;
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            // Check if already installed
            $checkResult = $ssh->execute(
                "docker exec {$site->container_name} wp core is-installed --allow-root"
            );

            if ($checkResult['success']) {
                Log::info("Remote WordPress already installed for {$site->site_name}");
                $ssh->disconnect();
                return true;
            }

            // Try to install
            $installCommand = sprintf(
                "docker exec %s wp core install " .
                "--url='http://%s:%s' " .
                "--title='%s' " .
                "--admin_user='%s' " .
                "--admin_password='%s' " .
                "--admin_email='%s' " .
                "--skip-email " .
                "--allow-root",
                $site->container_name,
                $site->domain,
                $site->port,
                addslashes($site->site_name),
                $site->admin_user,
                $site->admin_password,
                $site->admin_email
            );

            $installResult = $ssh->execute($installCommand);

            if ($installResult['success']) {
                Log::info("Remote WordPress successfully installed for {$site->site_name}");
                $ssh->disconnect();
                return true;
            }

            sleep(3);
            $attempts++;
        }

        Log::warning("Remote WordPress auto-install timed out for {$site->site_name}");
        $ssh->disconnect();
        return false;
    }

    /**
     * Check if WordPress is installed
     */
    public function isWordPressInstalled(WordPressSite $site): bool
    {
        try {
            if ($site->is_remote && $site->server) {
                return $this->isRemoteWordPressInstalled($site);
            } else {
                return $this->isLocalWordPressInstalled($site);
            }
        } catch (\Exception $e) {
            Log::error("Failed to check WordPress installation status: " . $e->getMessage());
            return false;
        }
    }

    private function isLocalWordPressInstalled(WordPressSite $site): bool
    {
        $process = new \Symfony\Component\Process\Process([
            'docker', 'exec', $site->container_name,
            'wp', 'core', 'is-installed', '--allow-root'
        ]);
        
        $process->run();
        return $process->isSuccessful();
    }

    private function isRemoteWordPressInstalled(WordPressSite $site): bool
    {
        $ssh = new SSHService($site->server);
        $ssh->connect();

        $result = $ssh->execute(
            "docker exec {$site->container_name} wp core is-installed --allow-root"
        );

        $ssh->disconnect();
        return $result['success'];
    }

    /**
     * Get WordPress version
     */
    public function getWordPressVersion(WordPressSite $site): ?string
    {
        try {
            if ($site->is_remote && $site->server) {
                $ssh = new SSHService($site->server);
                $ssh->connect();

                $result = $ssh->execute(
                    "docker exec {$site->container_name} wp core version --allow-root"
                );

                $ssh->disconnect();
                return $result['success'] ? trim($result['output']) : null;
            } else {
                $process = new \Symfony\Component\Process\Process([
                    'docker', 'exec', $site->container_name,
                    'wp', 'core', 'version', '--allow-root'
                ]);
                
                $process->run();
                return $process->isSuccessful() ? trim($process->getOutput()) : null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }
}