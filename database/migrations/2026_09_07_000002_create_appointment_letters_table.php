<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('appointment_letters')) {
            Schema::create('appointment_letters', function (Blueprint $table) {
                $table->id();
                $table->foreignId('application_id')->constrained('internship_applications')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->string('reference_number', 100)->unique();
                $table->string('file_path');
                $table->string('document_version', 50)->default('v1.0');
                $table->unsignedBigInteger('generated_by')->nullable();
                $table->timestamp('generated_at')->useCurrent();
                $table->timestamp('downloaded_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('generated_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_letters');
    }
};