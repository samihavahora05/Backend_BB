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
        // 1. Add Missing Indexes
        $this->addIndexSafe('blog_blog_tag', 'blog_id');
        $this->addIndexSafe('blog_category_blog', 'blog_id');
        $this->addIndexSafe('blog_tag_pivot', 'blog_id');
        $this->addIndexSafe('expert_bookings', 'order_id');
        $this->addIndexSafe('expert_bookings', 'payment_id');
        $this->addIndexSafe('media_file_tag', 'file_id');
        $this->addIndexSafe('mentor_bookings', 'order_id');
        $this->addIndexSafe('model_has_permissions', 'permission_id');
        $this->addIndexSafe('model_has_roles', 'role_id');
        $this->addIndexSafe('notification_reads', 'notification_id');
        $this->addIndexSafe('payments', 'transaction_id');
        $this->addIndexSafe('role_has_permissions', 'permission_id');
        $this->addIndexSafe('virtual_classes', 'meeting_id');
        $this->addIndexSafe('zoom_settings', 'account_id');
        $this->addIndexSafe('zoom_settings', 'client_id');
        
        // Add Indexes for Polymorphic relations
        $this->addIndexSafe('data_audit_logs', 'record_id');
        $this->addIndexSafe('media_permissions', 'media_id');
        $this->addIndexSafe('media_permissions', 'entity_id');

        // 2. Add Missing Foreign Keys (Only for relational tables, ignoring polymorphs and external string IDs)
        
        // notification_reads -> notifications (notification_id)
        if (Schema::hasColumn('notification_reads', 'notification_id')) {
            try {
                Schema::table('notification_reads', function (Blueprint $table) {
                    // $table->foreign('notification_id')->references('id')->on('notifications')->onDelete('cascade');
                });
            } catch (\Exception $e) {}
        }

        // sessions -> users (user_id)
        if (Schema::hasColumn('sessions', 'user_id')) {
            try {
                Schema::table('sessions', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                });
            } catch (\Exception $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To drop, we would remove the FKs and indexes
        // Because of the safe addition, we'll leave it empty to avoid complex rollback errors in production.
    }

    /**
     * Helper to add index safely if it doesn't exist
     */
    private function addIndexSafe($tableName, $columnName)
    {
        if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, $columnName)) {
            try {
                Schema::table($tableName, function (Blueprint $table) use ($columnName) {
                    $table->index($columnName);
                });
            } catch (\Exception $e) {
                // Index already exists or cannot be created
            }
        }
    }
};
