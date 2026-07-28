<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('phone');
            $table->text('message')->nullable()->after('subject');
            $table->string('course_interested')->nullable()->after('message');
            $table->string('source_page')->nullable()->after('source');
            $table->string('ip_address', 45)->nullable()->after('status');
            $table->text('browser')->nullable()->after('ip_address');
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete()->after('browser');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['assigned_admin_id']);
            $table->dropColumn([
                'subject', 
                'message', 
                'course_interested', 
                'source_page', 
                'ip_address', 
                'browser', 
                'assigned_admin_id'
            ]);
        });
    }
};
