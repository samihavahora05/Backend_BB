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
        if (Schema::hasTable('cms_companies') && !Schema::hasColumn('cms_companies', 'location')) {
            Schema::table('cms_companies', function (Blueprint $table) {
                $table->string('location')->nullable()->after('website_url');
            });
        }

        if (Schema::hasTable('cms_placement_partners') && !Schema::hasColumn('cms_placement_partners', 'location')) {
            Schema::table('cms_placement_partners', function (Blueprint $table) {
                $table->string('location')->nullable()->after('website_url');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cms_companies') && Schema::hasColumn('cms_companies', 'location')) {
            Schema::table('cms_companies', function (Blueprint $table) {
                $table->dropColumn('location');
            });
        }

        if (Schema::hasTable('cms_placement_partners') && Schema::hasColumn('cms_placement_partners', 'location')) {
            Schema::table('cms_placement_partners', function (Blueprint $table) {
                $table->dropColumn('location');
            });
        }
    }
};
