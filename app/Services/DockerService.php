<?php

namespace App\Services;

use App\Models\Server;
use App\Models\Site;
use Illuminate\Support\Facades\Log;
use phpseclib3\Net\SSH2;

class DockerService
{
    protected function connectToServer(Server $server)
    {
        $ssh = new SSH2($server->ip_address, $server->port);
        
        if ($server->private_key) {
            $key = \phpseclib3\Crypt\RSA::load($server->private_key);
            if (!$ssh->login($server->username, $key)) {
                throw new \Exception('SSH login failed');
            }
        } elseif ($server->password) {
            if (!$ssh->login($server->username, $server->password)) {
                throw new \Exception('SSH login failed');
            }
        } else {
            throw new \Exception('No authentication method provided');
        }
        
        return $ssh;
    }
    
    public function deploySite(Site $site)
    {
        try {
            $server = $site->server;
            $ssh = $this->connectToServer($server);
            
            // Create directory for the site
            $ssh->exec("mkdir -p {$server->path}/{$site->container_name}");
            
            // Generate Docker Compose file
            $composeContent = $this->generateDockerCompose($site);
            $ssh->exec("echo '{$composeContent}' > {$server->path}/{$site->container_name}/docker-compose.yml");
            
            // Generate WordPress configuration
            $wpConfigContent = $this->generateWpConfig($site);
            $ssh->exec("echo '{$wpConfigContent}' > {$server->path}/{$site->container_name}/wp-config.php");
            
            // Start the containers
            $ssh->exec("cd {$server->path}/{$site->container_name} && docker-compose up -d");
            
            // Wait for containers to be ready
            sleep(10);
            
            // Check if containers are running
            $output = $ssh->exec("cd {$server->path}/{$site->container_name} && docker-compose ps");
            
            if (strpos($output, 'Up') !== false) {
                $site->status = 'running';
                $site->last_deployed_at = now();
                $site->save();
                
                // Install WordPress
                $this->installWordPress($site, $ssh);
            } else {
                $site->status = 'failed';
                $site->save();
                
                Log::error('Failed to start containers for site: ' . $site->domain);
            }
            
            $ssh->disconnect();
        } catch (\Exception $e) {
            $site->status = 'failed';
            $site->save();
            
            Log::error('Error deploying site: ' . $e->getMessage());
        }
    }
    
    public function updateSite(Site $site)
    {
        try {
            $server = $site->server;
            $ssh = $this->connectToServer($server);
            
            // Stop the containers
            $ssh->exec("cd {$server->path}/{$site->container_name} && docker-compose down");
            
            // Update Docker Compose file
            $composeContent = $this->generateDockerCompose($site);
            $ssh->exec("echo '{$composeContent}' > {$server->path}/{$site->container_name}/docker-compose.yml");
            
            // Update WordPress configuration
            $wpConfigContent = $this->generateWpConfig($site);
            $ssh->exec("echo '{$wpConfigContent}' > {$server->path}/{$site->container_name}/wp-config.php");
            
            // Start the containers
            $ssh->exec("cd {$server->path}/{$site->container_name} && docker-compose up -d");
            
            // Wait for containers to be ready
            sleep(10);
            
            // Check if containers are running
            $output = $ssh->exec("cd {$server->path}/{$site->container_name} && docker-compose ps");
            
            if (strpos($output, 'Up') !== false) {
                $site->status = 'running';
                $site->last_deployed_at = now();
                $site->save();
            } else {
                $site->status = 'failed';
                $site->save();
                
                Log::error('Failed to update containers for site: ' . $site->domain);
            }
            
            $ssh->disconnect();
        } catch (\Exception $e) {
            $site->status = 'failed';
            $site->save();
            
            Log::error('Error updating site: ' . $e->getMessage());
        }
    }
    
    public function removeSite(Site $site)
    {
        try {
            $server = $site->server;
            $ssh = $this->connectToServer($server);
            
            // Stop and remove the containers
            $ssh->exec("cd {$server->path}/{$site->container_name} && docker-compose down -v");
            
            // Remove the directory
            $ssh->exec("rm -rf {$server->path}/{$site->container_name}");
            
            $ssh->disconnect();
        } catch (\Exception $e) {
            Log::error('Error removing site: ' . $e->getMessage());
        }
    }
    
    public function startSite(Site $site)
    {
        try {
            $server = $site->server;
            $ssh = $this->connectToServer($server);
            
            // Start the containers
            $ssh->exec("cd {$server->path}/{$site->container_name} && docker-compose start");
            
            // Check if containers are running
            $output = $ssh->exec("cd {$server->path}/{$site->container_name} && docker-compose ps");
            
            if (strpos($output, 'Up') !== false) {
                $site->status = 'running';
                $site->save();
            } else {
                $site->status = 'failed';
                $site->save();
                
                Log::error('Failed to start containers for site: ' . $site->domain);
            }
            
            $ssh->disconnect();
        } catch (\Exception $e) {
            $site->status = 'failed';
            $site->save();
            
            Log::error('Error starting site: ' . $e->getMessage());
        }
    }
    
    public function stopSite(Site $site)
    {
        try {
            $server = $site->server;
            $ssh = $this->connectToServer($server);
            
            // Stop the containers
            $ssh->exec("cd {$server->path}/{$site->container_name} && docker-compose stop");
            
            // Check if containers are stopped
            $output = $ssh->exec("cd {$server->path}/{$site->container_name} && docker-compose ps");
            
            if (strpos($output, 'Exit') !== false || strpos($output, 'Up') === false) {
                $site->status = 'stopped';
                $site->save();
            } else {
                $site->status = 'failed';
                $site->save();
                
                Log::error('Failed to stop containers for site: ' . $site->domain);
            }
            
            $ssh->disconnect();
        } catch (\Exception $e) {
            $site->status = 'failed';
            $site->save();
            
            Log::error('Error stopping site: ' . $e->getMessage());
        }
    }
    
    public function restartSite(Site $site)
    {
        try {
            $server = $site->server;
            $ssh = $this->connectToServer($server);
            
            // Restart the containers
            $ssh->exec("cd {$server->path}/{$site->container_name} && docker-compose restart");
            
            // Check if containers are running
            $output = $ssh->exec("cd {$server->path}/{$site->container_name} && docker-compose ps");
            
            if (strpos($output, 'Up') !== false) {
                $site->status = 'running';
                $site->save();
            } else {
                $site->status = 'failed';
                $site->save();
                
                Log::error('Failed to restart containers for site: ' . $site->domain);
            }
            
            $ssh->disconnect();
        } catch (\Exception $e) {
            $site->status = 'failed';
            $site->save();
            
            Log::error('Error restarting site: ' . $e->getMessage());
        }
    }
    
    protected function generateDockerCompose(Site $site)
    {
        $compose = [
            'version' => '3.8',
            'services' => [
                'wordpress' => [
                    'image' => 'wordpress:latest',
                    'container_name' => $site->container_name,
                    'restart' => 'unless-stopped',
                    'ports' => [
                        '80:80',
                        '443:443',
                    ],
                    'environment' => [
                        'WORDPRESS_DB_HOST' => 'db',
                        'WORDPRESS_DB_USER' => $site->database_user,
                        'WORDPRESS_DB_PASSWORD' => $site->database_password,
                        'WORDPRESS_DB_NAME' => $site->database_name,
                        'WORDPRESS_TABLE_PREFIX' => 'wp_',
                    ],
                    'volumes' => [
                        './wp-content:/var/www/html/wp-content',
                        './wp-config.php:/var/www/html/wp-config.php',
                    ],
                    'depends_on' => [
                        'db',
                    ],
                ],
                'db' => [
                    'image' => 'mysql:5.7',
                    'container_name' => $site->container_name . '_db',
                    'restart' => 'unless-stopped',
                    'environment' => [
                        'MYSQL_DATABASE' => $site->database_name,
                        'MYSQL_USER' => $site->database_user,
                        'MYSQL_PASSWORD' => $site->database_password,
                        'MYSQL_ROOT_PASSWORD' => 'rootpassword',
                    ],
                    'volumes' => [
                        'db_data:/var/lib/mysql',
                    ],
                ],
            ],
            'volumes' => [
                'db_data' => [
                    'driver' => 'local',
                ],
            ],
        ];
        
        // Add SSL configuration if enabled
        if ($site->ssl['enabled'] ?? false) {
            $compose['services']['wordpress']['volumes'][] = './ssl:/etc/nginx/ssl';
        }
        
        return json_encode($compose, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    protected function generateWpConfig(Site $site)
    {
        $config = "<?php\n";
        $config .= "/**\n";
        $config .= " * WordPress configuration file\n";
        $config .= " */\n\n";
        $config .= "// Database settings\n";
        $config .= "define('DB_NAME', '{$site->database_name}');\n";
        $config .= "define('DB_USER', '{$site->database_user}');\n";
        $config .= "define('DB_PASSWORD', '{$site->database_password}');\n";
        $config .= "define('DB_HOST', 'db');\n";
        $config .= "define('DB_CHARSET', 'utf8mb4');\n";
        $config .= "define('DB_COLLATE', '');\n\n";
        $config .= "// Authentication Unique Keys and Salts\n";
        $config .= "define('AUTH_KEY', '" . wp_generate_password(64, true) . "');\n";
        $config .= "define('SECURE_AUTH_KEY', '" . wp_generate_password(64, true) . "');\n";
        $config .= "define('LOGGED_IN_KEY', '" . wp_generate_password(64, true) . "');\n";
        $config .= "define('NONCE_KEY', '" . wp_generate_password(64, true) . "');\n";
        $config .= "define('AUTH_SALT', '" . wp_generate_password(64, true) . "');\n";
        $config .= "define('SECURE_AUTH_SALT', '" . wp_generate_password(64, true) . "');\n";
        $config .= "define('LOGGED_IN_SALT', '" . wp_generate_password(64, true) . "');\n";
        $config .= "define('NONCE_SALT', '" . wp_generate_password(64, true) . "');\n\n";
        $config .= "// WordPress Database Table prefix\n";
        $config .= "\$table_prefix = 'wp_';\n\n";
        $config .= "// WordPress absolute path to the WordPress directory\n";
        $config .= "if (!defined('ABSPATH')) {\n";
        $config .= "    define('ABSPATH', __DIR__ . '/');\n";
        $config .= "}\n\n";
        $config .= "// Sets up WordPress vars and included files\n";
        $config .= "require_once ABSPATH . 'wp-settings.php';\n";
        
        return $config;
    }
    
    protected function installWordPress(Site $site, SSH2 $ssh)
    {
        // Install WordPress CLI
        $ssh->exec("docker exec {$site->container_name} sh -c 'apt-get update && apt-get install -y wget less'");
        $ssh->exec("docker exec {$site->container_name} sh -c 'curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar'");
        $ssh->exec("docker exec {$site->container_name} sh -c 'chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp'");
        
        // Install WordPress
        $ssh->exec("docker exec {$site->container_name} wp core install --url={$site->domain} --title='{$site->domain}' --admin_user={$site->admin_username} --admin_password={$site->admin_password} --admin_email={$site->admin_email}");
        
        // Install SSL certificate if enabled
        if ($site->ssl['enabled'] ?? false) {
            $ssh->exec("docker exec {$site->container_name} sh -c 'apt-get update && apt-get install -y certbot'");
            $ssh->exec("docker exec {$site->container_name} sh -c 'certbot certonly --webroot -w /var/www/html -d {$site->domain} --non-interactive --agree-tos --email {$site->admin_email}'");
        }
    }
}