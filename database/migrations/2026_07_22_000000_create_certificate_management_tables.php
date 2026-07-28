<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Drop old tables from previous dummy migrations if they exist
        Schema::dropIfExists('certificate_downloads');
        Schema::dropIfExists('certificate_logs');
        Schema::dropIfExists('certificate_verifications');
        Schema::dropIfExists('certificate_qr_codes');
        Schema::dropIfExists('issued_certificates');
        Schema::dropIfExists('certificate_settings');
        Schema::dropIfExists('certificate_templates');
        Schema::dropIfExists('certificate_signatures');
        Schema::dropIfExists('certificate_fonts');

        // 1. Certificate Fonts
        Schema::create('certificate_fonts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_path');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Certificate Signatures
        Schema::create('certificate_signatures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image_path');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Certificate Templates
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('background_image_path');
            $table->foreignId('default_font_id')->nullable()->constrained('certificate_fonts')->nullOnDelete();
            $table->foreignId('default_signature_id')->nullable()->constrained('certificate_signatures')->nullOnDelete();
            
            // Dimensions & Layout settings stored as JSON
            $table->json('layout_settings')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Certificate Settings
        Schema::create('certificate_settings', function (Blueprint $table) {
            $table->id();
            $table->string('prefix')->default('CERT-');
            $table->string('number_format')->default('YYYY-XXXX');
            $table->foreignId('default_template_id')->nullable()->constrained('certificate_templates')->nullOnDelete();
            $table->boolean('enable_qr_code')->default(true);
            $table->boolean('enable_verification')->default(true);
            $table->boolean('auto_generate')->default(false);
            $table->boolean('auto_email')->default(false);
            $table->string('date_format')->default('F j, Y');
            $table->integer('expiry_days')->nullable(); // null means no expiry
            $table->timestamps();
        });

        // 5. Issued Certificates
        Schema::create('issued_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('certificate_templates')->nullOnDelete();
            $table->string('status')->default('Issued'); // Issued, Revoked, Expired, Pending
            
            $table->float('completion_percentage')->nullable();
            $table->string('grade')->nullable();
            $table->text('remarks')->nullable();
            
            $table->string('pdf_path')->nullable();
            
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 6. Certificate QR Codes
        Schema::create('certificate_qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issued_certificate_id')->constrained('issued_certificates')->cascadeOnDelete();
            $table->string('qr_code_path');
            $table->string('target_url');
            $table->timestamps();
        });

        // 7. Certificate Verifications
        Schema::create('certificate_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issued_certificate_id')->constrained('issued_certificates')->cascadeOnDelete();
            $table->string('verification_token')->unique();
            $table->string('verification_url');
            $table->integer('verification_count')->default(0);
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();
        });

        // 8. Certificate Downloads
        Schema::create('certificate_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issued_certificate_id')->constrained('issued_certificates')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Who downloaded it
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        // 9. Certificate Logs
        Schema::create('certificate_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issued_certificate_id')->nullable()->constrained('issued_certificates')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // User who performed action
            $table->string('action'); // e.g., 'Issued', 'Revoked', 'Emailed', 'Deleted'
            $table->text('description')->nullable();
            $table->timestamps();
        });
        
        // Remove the old simple 'certificates' table if it exists (since we're upgrading)
        Schema::dropIfExists('certificates');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_logs');
        Schema::dropIfExists('certificate_downloads');
        Schema::dropIfExists('certificate_verifications');
        Schema::dropIfExists('certificate_qr_codes');
        Schema::dropIfExists('issued_certificates');
        Schema::dropIfExists('certificate_settings');
        Schema::dropIfExists('certificate_templates');
        Schema::dropIfExists('certificate_signatures');
        Schema::dropIfExists('certificate_fonts');
    }
};
