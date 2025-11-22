<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('servers')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('domain');
            $table->string('container_name')->unique();
            $table->string('status')->default('pending'); // pending, running, stopped, failed, deploying
            $table->string('database_name');
            $table->string('database_user');
            $table->string('database_password'); // Encrypted
            $table->string('admin_username');
            $table->string('admin_password'); // Encrypted
            $table->string('admin_email');
            $table->json('ssl')->nullable(); // SSL configuration
            $table->json('firewall_rules')->nullable(); // Firewall rules
            $table->timestamp('last_deployed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
