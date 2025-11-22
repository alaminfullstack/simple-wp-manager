<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Site>
 */
class SiteFactory extends Factory
{
    public function definition()
    {
        return [
            'server_id' => Server::factory(),
            'user_id' => User::factory(),
            'domain' => $this->faker->domainName(),
            'container_name' => $this->faker->slug(),
            'status' => 'running',
            'database_name' => $this->faker->word(),
            'database_user' => $this->faker->userName(),
            'database_password' => $this->faker->password(),
            'admin_username' => 'admin',
            'admin_password' => $this->faker->password(),
            'admin_email' => $this->faker->email(),
            'ssl' => [
                'enabled' => true,
                'certificate' => null,
                'key' => null,
            ],
            'firewall_rules' => [
                'allow_http' => true,
                'allow_https' => true,
                'allow_ssh' => true,
            ],
        ];
    }
}
