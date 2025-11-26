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
        Schema::create('wordpress_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('server_id')->nullable()->constrained('servers')->onDelete('cascade');
            $table->boolean('is_remote')->default(false);
            $table->string('site_name');
            $table->string('domain');
            $table->integer('port')->unique();
            $table->string('container_name')->unique();
            $table->string('db_name');
            $table->string('db_user');
            $table->string('db_password');
            $table->string('db_root_password');
            $table->enum('status', ['creating', 'deploying', 'running', 'stopped', 'deleting', 'error', 'failed'])->default('creating');
            $table->text('error_message')->nullable();
            $table->string('admin_email')->nullable();
            $table->string('admin_user')->nullable();
            $table->string('admin_password')->nullable();
            $table->timestamps();
         ;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wordpress_sites');
    }
};
