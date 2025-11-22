<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\Server;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all server IDs to associate sites with them
        $serverIds = Server::pluck('id');

        // If no servers exist, we can't create sites.
        if ($serverIds->isEmpty()) {
            $this->command->warn('No servers found. Skipping SiteSeeder.');
            return;
        }

        // Create 15 sample sites, randomly assigning them to a server
        Site::factory()->count(15)->create([
            'server_id' => $serverIds->random(),
        ]);
    }
}
