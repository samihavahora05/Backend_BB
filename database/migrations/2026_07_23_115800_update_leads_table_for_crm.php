<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Duplicate migration from earlier - do nothing to allow migrate to complete successfully.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['type', 'internal_notes']);
        });

        DB::statement("ALTER TABLE leads MODIFY COLUMN status ENUM('new', 'contacted', 'in_progress', 'converted', 'dead') DEFAULT 'new'");
    }
};
