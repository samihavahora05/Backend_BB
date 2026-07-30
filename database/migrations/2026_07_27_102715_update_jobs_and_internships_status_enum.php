<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            // Update internships table status enum
            DB::statement("ALTER TABLE internships MODIFY COLUMN status ENUM('draft', 'pending', 'open', 'active', 'closed', 'archived', 'rejected') DEFAULT 'draft'");
            
            // Update jobs table status enum
            DB::statement("ALTER TABLE jobs MODIFY COLUMN status ENUM('draft', 'pending', 'pending_approval', 'open', 'active', 'expired', 'closed', 'archived', 'rejected') DEFAULT 'draft'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert is not easily possible with ENUMs without data loss, so left empty or basic
    }
};
