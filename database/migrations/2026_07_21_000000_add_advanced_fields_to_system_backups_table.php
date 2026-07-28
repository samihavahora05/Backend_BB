<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('system_backups', function (Blueprint $table) {
            if (!Schema::hasColumn('system_backups', 'checksum')) {
                $table->string('checksum')->nullable();
            }
            if (!Schema::hasColumn('system_backups', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }
            if (!Schema::hasColumn('system_backups', 'restore_logs')) {
                $table->json('restore_logs')->nullable();
            }
            if (!Schema::hasColumn('system_backups', 'duration')) {
                $table->integer('duration')->nullable()->comment('Duration in seconds');
            }
        });
    }

    public function down()
    {
        Schema::table('system_backups', function (Blueprint $table) {
            $table->dropColumn(['checksum', 'completed_at', 'restore_logs', 'duration']);
        });
    }
};
