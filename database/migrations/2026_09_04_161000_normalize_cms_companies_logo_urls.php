<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Safely normalize existing company and placement partner logo URLs in the database.
     */
    public function up(): void
    {
        if (Schema::hasTable('cms_companies')) {
            // Normalize Otto Valves & Rubers logo
            DB::table('cms_companies')
                ->where('name', 'like', '%Otto Valves%')
                ->orWhere('slug', 'like', '%otto-valves%')
                ->update([
                    'logo_url' => '/logo/otto-valves-rubers.png'
                ]);

            // Normalize Asha Tours & Travels logo
            DB::table('cms_companies')
                ->where('name', 'like', '%Asha Tours%')
                ->orWhere('slug', 'like', '%asha-tours%')
                ->update([
                    'logo_url' => '/logo/Asha_tours-travels.jpeg'
                ]);
        }

        if (Schema::hasTable('cms_placement_partners')) {
            DB::table('cms_placement_partners')
                ->where('name', 'like', '%Asha Tours%')
                ->update([
                    'logo_url' => '/logo/Asha_tours-travels.jpeg'
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive: no rollback needed for data cleanup
    }
};
