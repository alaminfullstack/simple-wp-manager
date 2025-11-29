<?php

namespace App\Services;

use App\Models\Server;
use phpseclib3\Net\SSH2;
use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\PublicKeyLoader;

class SSHService
{
    private $ssh;
    private $sftp;
    private $server;

    public function __construct(Server $server = null)
    {
        $this->server = $server;
    }

    public function connect(Server $server = null): bool
    {
        if ($server) {
            $this->server = $server;
        }

        if (!$this->server) {
            throw new \Exception('No server specified for SSH connection');
        }

        // Check if local server
        if ($this->server->isLocal()) {
            return true; // Skip SSH for localhost
        }

        try {
            $this->ssh = new SSH2($this->server->ip_address, $this->server->ssh_port);

            // Connect using password or key
            if ($this->server->connection_type === 'key' && $this->server->ssh_key) {
                $key = PublicKeyLoader::load($this->server->ssh_key);
                $login = $this->ssh->login($this->server->ssh_user, $key);
            } else {
                $login = $this->ssh->login($this->server->ssh_user, $this->server->ssh_password);
            }

            if (!$login) {
                throw new \Exception('SSH authentication failed');
            }

            // Initialize SFTP connection
            $this->sftp = new SFTP($this->server->ip_address, $this->server->ssh_port);
            
            if ($this->server->connection_type === 'key' && $this->server->ssh_key) {
                $key = PublicKeyLoader::load($this->server->ssh_key);
                $sftpLogin = $this->sftp->login($this->server->ssh_user, $key);
            } else {
                $sftpLogin = $this->sftp->login($this->server->ssh_user, $this->server->ssh_password);
            }

            if (!$sftpLogin) {
                throw new \Exception('SFTP authentication failed');
            }

            // Update server status
            $this->server->update([
                'status' => 'active',
                'last_connected_at' => now(),
                'last_error' => null
            ]);

            return true;
        } catch (\Exception $e) {
            $this->server->update([
                'status' => 'error',
                'last_error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function execute(string $command): array
    {
        // If local server, use local execution
        if ($this->server && $this->server->isLocal()) {
            return $this->executeLocal($command);
        }

        if (!$this->ssh) {
            $this->connect();
        }

        try {
            $output = $this->ssh->exec($command);
            $exitStatus = $this->ssh->getExitStatus();

            return [
                'success' => $exitStatus === 0,
                'output' => $output,
                'exit_code' => $exitStatus
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
                'exit_code' => 1
            ];
        }
    }

    private function executeLocal(string $command): array
    {
        $process = new \Symfony\Component\Process\Process(explode(' ', $command));
        $process->run();

        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'exit_code' => $process->getExitStatus()
        ];
    }

    public function uploadFile(string $localPath, string $remotePath): bool
    {
        if ($this->server && $this->server->isLocal()) {
            return copy($localPath, $remotePath);
        }

        if (!$this->sftp) {
            $this->connect();
        }

        try {
            return $this->sftp->put($remotePath, $localPath, SFTP::SOURCE_LOCAL_FILE);
        } catch (\Exception $e) {
            throw new \Exception("File upload failed: " . $e->getMessage());
        }
    }

    public function downloadFile(string $remotePath, string $localPath): bool
    {
        if ($this->server && $this->server->isLocal()) {
            return copy($remotePath, $localPath);
        }

        if (!$this->sftp) {
            $this->connect();
        }

        try {
            return $this->sftp->get($remotePath, $localPath);
        } catch (\Exception $e) {
            throw new \Exception("File download failed: " . $e->getMessage());
        }
    }

    public function fileExists(string $remotePath): bool
    {
        if ($this->server && $this->server->isLocal()) {
            return file_exists($remotePath);
        }

        if (!$this->sftp) {
            $this->connect();
        }

        try {
            return $this->sftp->file_exists($remotePath);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createDirectory(string $remotePath, int $mode = 0755, bool $useSudo = true): bool
    {
        if ($this->server && $this->server->isLocal()) {
            if (!file_exists($remotePath)) {
                return mkdir($remotePath, $mode, true);
            }
            return true;
        }

        if (!$this->sftp) {
            $this->connect();
        }

        try {
            // Try SFTP first (without sudo)
            $result = $this->sftp->mkdir($remotePath, $mode, true);
            
            // Verify directory was created
            if (!$this->sftp->file_exists($remotePath)) {
                // Fallback to SSH command with sudo
                \Log::warning("SFTP mkdir failed, trying SSH command with sudo for: {$remotePath}");
                
                $mkdirCommand = $useSudo 
                    ? "sudo mkdir -p {$remotePath} && sudo chmod " . decoct($mode) . " {$remotePath} && sudo chown \$USER:\$USER {$remotePath}"
                    : "mkdir -p {$remotePath} && chmod " . decoct($mode) . " {$remotePath}";
                
                $sshResult = $this->execute($mkdirCommand);
                
                if (!$sshResult['success']) {
                    throw new \Exception("SSH mkdir failed: " . ($sshResult['output'] ?? 'Unknown error'));
                }
                
                return true;
            }
            
            return $result;
        } catch (\Exception $e) {
            // Final fallback to SSH command with sudo
            \Log::warning("SFTP mkdir exception, trying SSH command with sudo: " . $e->getMessage());
            
            $mkdirCommand = $useSudo 
                ? "sudo mkdir -p {$remotePath} && sudo chmod " . decoct($mode) . " {$remotePath} && sudo chown \$USER:\$USER {$remotePath}"
                : "mkdir -p {$remotePath} && chmod " . decoct($mode) . " {$remotePath}";
            
            $sshResult = $this->execute($mkdirCommand);
            
            if (!$sshResult['success']) {
                throw new \Exception("Directory creation failed: " . $e->getMessage() . " | SSH: " . ($sshResult['output'] ?? 'Unknown error'));
            }
            
            return true;
        }
    }

    public function testConnection(): array
    {
        try {
            $this->connect();
            
            $result = $this->execute('echo "Connection test successful"');
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'output' => $result['output']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function disconnect(): void
    {
        if ($this->ssh) {
            $this->ssh->disconnect();
            $this->ssh = null;
        }
        
        if ($this->sftp) {
            $this->sftp->disconnect();
            $this->sftp = null;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}