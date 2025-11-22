<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Models\WordPressSite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WordPressSite>
 */
class WordPressSiteFactory extends Factory
{
    protected $model = WordPressSite::class;

    public function definition(): array
    {
        $domain = $this->faker->domainName();
        
        return [
            'name' => $this->faker->company() . ' Website',
            'domain' => $domain,
            'container_name' => 'wp_' . Str::slug($domain, '_'),
            'server_ip' => $this->faker->ipv4(),
            'server_port' => 22,
            'server_username' => 'root',
            'server_password' => $this->faker->password(16),
            'server_ssh_key' => null,
            'wp_version' => 'latest',
            'container_port' => $this->faker->numberBetween(8080, 9000),
            'db_name' => 'wp_' . Str::random(8),
            'db_user' => 'wp_user_' . Str::random(6),
            'db_password' => Str::random(16),
            'db_host' => 'mysql',
            'status' => $this->faker->randomElement(['running', 'stopped', 'deploying', 'failed']),
            'status_message' => $this->faker->optional()->sentence(),
            'last_checked_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'ssl_enabled' => $this->faker->boolean(30),
            'docker_config' => [
                'memory_limit' => '512m',
                'cpu_limit' => '1.0',
            ],
        ];
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'running',
            'status_message' => 'Container is running normally',
        ]);
    }

    public function stopped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'stopped',
            'status_message' => 'Container has been stopped',
        ]);
    }

    public function deploying(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'deploying',
            'status_message' => 'Deployment in progress',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'status_message' => 'Failed to start container',
        ]);
    }
}
