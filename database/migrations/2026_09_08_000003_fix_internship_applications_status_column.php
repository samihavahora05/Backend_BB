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
        if (Schema::hasTable('internship_applications')) {
            if (DB::connection()->getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `internship_applications` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'applied'");
            } else {
                Schema::table('internship_applications', function (Blueprint $table) {
                    $table->string('status', 50)->default('applied')->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('internship_applications') && DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `internship_applications` MODIFY COLUMN `status` ENUM('applied', 'under_review', 'shortlisted', 'interview', 'rejected', 'selected', 'offer_sent', 'joined', 'completed', 'approved', 'submitted') DEFAULT 'applied'");
        }
    }
};
