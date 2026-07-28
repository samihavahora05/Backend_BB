<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intern_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('internship_domain')->nullable();
            $table->foreignId('assigned_company')->nullable()->constrained('users')->onDelete('set null'); // Assuming company is a user
            $table->foreignId('mentor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('progress')->default(0);
            $table->integer('attendance')->default(0);
            $table->enum('certificate_status', ['pending', 'issued', 'rejected'])->default('pending');
            $table->json('skills')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intern_profiles');
    }
};
