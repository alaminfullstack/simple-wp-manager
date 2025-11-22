<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Server>
 */
class ServerFactory extends Factory
{
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->company(),
            'ip_address' => $this->faker->ipv4(),
            'port' => 22,
            'username' => 'root',
            'private_key' => '-----BEGIN RSA PRIVATE KEY-----\nMIIEpAIBAAKCAQEAwJNLvZ...\n-----END RSA PRIVATE KEY-----',
            'path' => '/var/www',
            'active' => true,
        ];
    }
}
