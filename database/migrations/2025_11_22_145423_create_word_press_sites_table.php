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
        Schema::create('word_press_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->unique();
            $table->string('container_name')->unique();
            
            // Server connection details (encrypted)
            $table->text('server_ip');
            $table->integer('server_port')->default(22);
            $table->text('server_username');
            $table->text('server_password')->nullable();
            $table->text('server_ssh_key')->nullable();
            
            // WordPress configuration
            $table->string('wp_version')->default('latest');
            $table->integer('container_port')->default(8080);
            $table->text('db_name');
            $table->text('db_user');
            $table->text('db_password');
            $table->string('db_host')->default('mysql');
            
            // Status and metadata
            $table->enum('status', ['running', 'stopped', 'deploying', 'failed'])->default('deploying');
            $table->text('status_message')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->boolean('ssl_enabled')->default(false);
            $table->json('docker_config')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('word_press_sites');
    }
};
