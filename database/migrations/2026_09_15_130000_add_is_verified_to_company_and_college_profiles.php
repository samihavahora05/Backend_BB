<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_profiles')) {
            Schema::table('company_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('company_profiles', 'is_verified')) {
                    $table->boolean('is_verified')->default(false);
                }
            });
        }

        if (Schema::hasTable('college_profiles')) {
            Schema::table('college_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('college_profiles', 'is_verified')) {
                    $table->boolean('is_verified')->default(false);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('company_profiles')) {
            Schema::table('company_profiles', function (Blueprint $table) {
                if (Schema::hasColumn('company_profiles', 'is_verified')) {
                    $table->dropColumn('is_verified');
                }
            });
        }

        if (Schema::hasTable('college_profiles')) {
            Schema::table('college_profiles', function (Blueprint $table) {
                if (Schema::hasColumn('college_profiles', 'is_verified')) {
                    $table->dropColumn('is_verified');
                }
            });
        }
    }
};
