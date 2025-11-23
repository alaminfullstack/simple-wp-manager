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
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('ip_address');
            $table->integer('ssh_port')->default(22);
            $table->string('ssh_user');
            $table->text('ssh_password')->nullable(); // Encrypted
            $table->text('ssh_key')->nullable(); // Encrypted private key
            $table->enum('connection_type', ['password', 'key'])->default('password');
            $table->enum('status', ['active', 'inactive', 'error'])->default('active');
            $table->text('last_error')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
