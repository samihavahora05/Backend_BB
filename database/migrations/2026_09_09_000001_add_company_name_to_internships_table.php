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
        if (Schema::hasTable('internships')) {
            Schema::table('internships', function (Blueprint $table) {
                if (!Schema::hasColumn('internships', 'company_name')) {
                    $table->string('company_name', 255)->nullable()->after('title');
                }
                if (!Schema::hasColumn('internships', 'company')) {
                    $table->string('company', 255)->nullable()->after('company_name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('internships')) {
            Schema::table('internships', function (Blueprint $table) {
                if (Schema::hasColumn('internships', 'company_name')) {
                    $table->dropColumn('company_name');
                }
                if (Schema::hasColumn('internships', 'company')) {
                    $table->dropColumn('company');
                }
            });
        }
    }
};