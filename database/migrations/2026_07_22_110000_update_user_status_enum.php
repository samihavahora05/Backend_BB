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
        // Altering ENUMs directly can be tricky across environments, so changing to a string is safest
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('active')->change();
        });
        
        // Just in case it fails due to Doctrine requirements, a raw DB statement guarantees it for MySQL:
        // DB::statement("ALTER TABLE users MODIFY COLUMN status VARCHAR(255) DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert back to original enum
            // DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'suspended') DEFAULT 'active'");
        });
    }
};
