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
        Schema::table('leads', function (Blueprint $table) {
            $table->string('type', 100)->default('Contact Inquiry')->after('id');
            $table->text('internal_notes')->nullable()->after('browser');
            
            // Note: Since we are using string for status in some places or enum in others,
            // we will just ensure it can hold the new values: 'Closed', 'Spam'. 
            // In MySQL, modifying an enum requires redefining it. If it's a string, we don't need to change it.
            // Let's assume it's a string based on previous migration (it didn't define it as enum).
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['type', 'internal_notes']);
        });
    }
};
