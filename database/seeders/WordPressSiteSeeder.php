<?php

namespace Database\Seeders;

use App\Models\WordPressSite;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class WordPressSiteSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample sites with different statuses
        WordPressSite::factory()->running()->create([
            'name' => 'Main Production Site',
            'domain' => 'example.com',
            'container_name' => 'wp_example_com',
        ]);

        WordPressSite::factory()->running()->create([
            'name' => 'Client Portfolio',
            'domain' => 'client-portfolio.com',
            'container_name' => 'wp_client_portfolio',
        ]);

        WordPressSite::factory()->stopped()->create([
            'name' => 'Staging Environment',
            'domain' => 'staging.example.com',
            'container_name' => 'wp_staging_example',
        ]);

        WordPressSite::factory()->deploying()->create([
            'name' => 'New Client Site',
            'domain' => 'newclient.com',
            'container_name' => 'wp_newclient',
        ]);

        WordPressSite::factory()->failed()->create([
            'name' => 'Failed Deployment',
            'domain' => 'failed-site.com',
            'container_name' => 'wp_failed_site',
        ]);

        // Create additional random sites
        WordPressSite::factory()->count(5)->create();
    }
}
