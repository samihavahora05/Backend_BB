<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('restore_logs');
        Schema::dropIfExists('backup_logs');
        Schema::dropIfExists('backup_settings');
        Schema::dropIfExists('system_backups');

        Schema::enableForeignKeyConstraints();

        // 1. System Backups
        Schema::create('system_backups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., blueboxx_db_backup_2023_10_26.sql
            $table->string('type'); // Database, Files, Complete
            $table->string('size')->nullable(); // e.g. "145 MB" or store bytes as unsignedBigInteger
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('status')->default('in_progress'); // in_progress, completed, failed
            $table->string('disk')->default('local'); // local, s3, etc.
            $table->string('file_path'); 
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Backup Settings
        Schema::create('backup_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // auto_backup_enabled, backup_frequency, etc.
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // 3. Backup Logs
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backup_id')->nullable()->constrained('system_backups')->nullOnDelete();
            $table->string('action'); // created, deleted, uploaded
            $table->text('message')->nullable();
            $table->timestamps();
        });

        // 4. Restore Logs
        Schema::create('restore_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backup_id')->constrained('system_backups')->cascadeOnDelete();
            $table->foreignId('restored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status'); // started, completed, failed
            $table->text('details')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('restore_logs');
        Schema::dropIfExists('backup_logs');
        Schema::dropIfExists('backup_settings');
        Schema::dropIfExists('system_backups');

        Schema::enableForeignKeyConstraints();
    }
};
