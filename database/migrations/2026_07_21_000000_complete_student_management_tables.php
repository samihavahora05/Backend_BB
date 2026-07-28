<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Ensure users and student_profiles have deleted_at
        if (!Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
        if (!Schema::hasColumn('student_profiles', 'deleted_at')) {
            Schema::table('student_profiles', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Add missing fields to student_profiles
        Schema::table('student_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('student_profiles', 'student_type')) {
                $table->string('student_type')->nullable(); // college, working, school
            }
            if (!Schema::hasColumn('student_profiles', 'job_title')) {
                $table->string('job_title')->nullable();
            }
            if (!Schema::hasColumn('student_profiles', 'company_name')) {
                $table->string('company_name')->nullable();
            }
            if (!Schema::hasColumn('student_profiles', 'identification_number')) {
                $table->string('identification_number')->nullable();
            }
            if (!Schema::hasColumn('student_profiles', 'pin')) {
                $table->string('pin')->nullable();
            }
            if (!Schema::hasColumn('student_profiles', 'bio')) {
                $table->text('bio')->nullable();
            }
        });

        // 1. student_settings
        if (!Schema::hasTable('student_settings')) {
            Schema::create('student_settings', function (Blueprint $table) {
                $table->id();
                $table->string('setting_key')->unique();
                $table->text('setting_value')->nullable();
                $table->timestamps();
            });
        }

        // 2. student_import_logs
        if (!Schema::hasTable('student_import_logs')) {
            Schema::create('student_import_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('filename');
                $table->integer('total_rows')->default(0);
                $table->integer('imported_rows')->default(0);
                $table->integer('skipped_rows')->default(0);
                $table->integer('failed_rows')->default(0);
                $table->text('errors')->nullable(); // JSON array of row errors
                $table->timestamps();
            });
        }

        // 3. student_exports
        if (!Schema::hasTable('student_exports')) {
            Schema::create('student_exports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('file_path');
                $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
                $table->string('format')->default('csv'); // csv, excel, pdf
                $table->timestamps();
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('student_exports');
        Schema::dropIfExists('student_import_logs');
        Schema::dropIfExists('student_settings');

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn(['student_type', 'job_title', 'company_name', 'identification_number', 'pin', 'bio']);
            if (Schema::hasColumn('student_profiles', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::enableForeignKeyConstraints();
    }
};
