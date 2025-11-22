<?php

namespace App\Services;

use Exception;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

class SSHService
{
    protected SSH2 $ssh;
    protected string $host;
    protected int $port;

    public function __construct(string $host, int $port = 22)
    {
        $this->host = $host;
        $this->port = $port;
        $this->ssh = new SSH2($host, $port);
        $this->ssh->setTimeout(30);
    }

    /**
     * Connect using password authentication
     */
    public function connectWithPassword(string $username, string $password): bool
    {
        try {
            if (!$this->ssh->login($username, $password)) {
                throw new Exception('SSH authentication failed with password');
            }
            return true;
        } catch (Exception $e) {
            throw new Exception("SSH connection failed: " . $e->getMessage());
        }
    }

    /**
     * Connect using SSH key authentication
     */
    public function connectWithKey(string $username, string $privateKey): bool
    {
        try {
            $key = PublicKeyLoader::load($privateKey);
            if (!$this->ssh->login($username, $key)) {
                throw new Exception('SSH authentication failed with key');
            }
            return true;
        } catch (Exception $e) {
            throw new Exception("SSH key authentication failed: " . $e->getMessage());
        }
    }

    /**
     * Execute a command on the remote server
     */
    public function execute(string $command): array
    {
        $output = $this->ssh->exec($command);
        $exitStatus = $this->ssh->getExitStatus();
        
        return [
            'output' => $output,
            'exit_status' => $exitStatus,
            'success' => $exitStatus === 0,
        ];
    }

    /**
     * Execute multiple commands
     */
    public function executeMultiple(array $commands): array
    {
        $results = [];
        foreach ($commands as $command) {
            $results[] = $this->execute($command);
        }
        return $results;
    }

    /**
     * Upload a file to the remote server
     */
    public function uploadFile(string $localPath, string $remotePath): bool
    {
        return $this->ssh->put($remotePath, $localPath, SSH2::SOURCE_LOCAL_FILE);
    }

    /**
     * Create a file with content on remote server
     */
    public function createFile(string $remotePath, string $content): bool
    {
        return $this->ssh->put($remotePath, $content);
    }

    /**
     * Check if a command exists on the remote server
     */
    public function commandExists(string $command): bool
    {
        $result = $this->execute("which {$command}");
        return $result['success'];
    }

    /**
     * Check if Docker is installed
     */
    public function isDockerInstalled(): bool
    {
        return $this->commandExists('docker');
    }

    /**
     * Check if Docker Compose is installed
     */
    public function isDockerComposeInstalled(): bool
    {
        return $this->commandExists('docker-compose') || $this->commandExists('docker compose');
    }

    /**
     * Disconnect SSH connection
     */
    public function disconnect(): void
    {
        $this->ssh->disconnect();
    }

    /**
     * Get the underlying SSH2 instance
     */
    public function getConnection(): SSH2
    {
        return $this->ssh;
    }
}