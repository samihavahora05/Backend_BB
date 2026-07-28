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
        Schema::create('delete_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('notes')->nullable(); // Admin notes
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('delete_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delete_request_id')->constrained('delete_requests')->onDelete('cascade');
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action'); // e.g., 'created', 'status_changed', 'approved', 'rejected'
            $table->text('notes')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delete_request_logs');
        Schema::dropIfExists('delete_requests');
    }
};
