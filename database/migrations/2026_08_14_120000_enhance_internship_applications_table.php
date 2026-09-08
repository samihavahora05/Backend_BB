<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('internship_applications')) {
            // In MySQL/InnoDB, dropping the composite unique index (internship_id, user_id) fails if
            // the foreign key on internship_id relies on it as its supporting index.
            // Adding a standalone index on internship_id first satisfies the foreign key.
            try {
                DB::statement("ALTER TABLE `internship_applications` ADD INDEX `internship_applications_internship_id_idx` (`internship_id`)");
            } catch (\Throwable $e) {
                // Ignore if index already exists or unsupported by engine
            }

            try {
                DB::statement("ALTER TABLE `internship_applications` DROP INDEX `internship_applications_internship_id_user_id_unique`");
            } catch (\Throwable $e) {
                // Ignore if unique index does not exist or was already dropped
            }

            Schema::table('internship_applications', function (Blueprint $table) {
                if (Schema::hasColumn('internship_applications', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->change();
                }
                if (Schema::hasColumn('internship_applications', 'internship_id')) {
                    $table->unsignedBigInteger('internship_id')->nullable()->change();
                }

                if (!Schema::hasColumn('internship_applications', 'first_name')) {
                    $table->string('first_name')->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('internship_applications', 'last_name')) {
                    $table->string('last_name')->nullable()->after('first_name');
                }
                if (!Schema::hasColumn('internship_applications', 'email')) {
                    $table->string('email')->nullable()->after('last_name');
                }
                if (!Schema::hasColumn('internship_applications', 'phone')) {
                    $table->string('phone')->nullable()->after('email');
                }
                if (!Schema::hasColumn('internship_applications', 'degree')) {
                    $table->string('degree')->nullable()->after('phone');
                }
                if (!Schema::hasColumn('internship_applications', 'graduation_year')) {
                    $table->string('graduation_year')->nullable()->after('degree');
                }
                if (!Schema::hasColumn('internship_applications', 'message')) {
                    $table->text('message')->nullable()->after('graduation_year');
                }
                if (!Schema::hasColumn('internship_applications', 'application_type')) {
                    $table->string('application_type')->nullable()->after('message');
                }
                if (!Schema::hasColumn('internship_applications', 'source_page')) {
                    $table->string('source_page')->nullable()->after('application_type');
                }
                if (!Schema::hasColumn('internship_applications', 'experience_years')) {
                    $table->string('experience_years')->nullable()->after('source_page');
                }
                if (!Schema::hasColumn('internship_applications', 'current_company')) {
                    $table->string('current_company')->nullable()->after('experience_years');
                }
                if (!Schema::hasColumn('internship_applications', 'available_from')) {
                    $table->string('available_from')->nullable()->after('current_company');
                }
                if (!Schema::hasColumn('internship_applications', 'expected_stipend')) {
                    $table->string('expected_stipend')->nullable()->after('available_from');
                }
                if (!Schema::hasColumn('internship_applications', 'custom_fields')) {
                    $table->json('custom_fields')->nullable()->after('expected_stipend');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('internship_applications')) {
            Schema::table('internship_applications', function (Blueprint $table) {
                $columns = [
                    'first_name', 'last_name', 'email', 'phone', 'degree',
                    'graduation_year', 'message', 'application_type', 'source_page',
                    'experience_years', 'current_company', 'available_from',
                    'expected_stipend', 'custom_fields'
                ];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('internship_applications', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
