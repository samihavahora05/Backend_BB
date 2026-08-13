<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Root cause for "saved internships not appearing / save throws an error":
 * migration 2026_07_17_100000_create_enterprise_internships_tables.php drops
 * `saved_internships` (to avoid FK conflicts while it rebuilds `internships`)
 * but never recreates it. The SavedInternship model, StudentWishlistController,
 * and every save/unsave-internship route all assume this table exists, so any
 * bookmark or wishlist-index call for internships hits a "table doesn't exist"
 * SQL error. Jobs/courses were unaffected — job_bookmarks and wishlists are
 * both recreated correctly in their respective migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('saved_internships')) {
            Schema::create('saved_internships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('internship_id')->constrained('internships')->onDelete('cascade');
                $table->timestamps();
                $table->unique(['user_id', 'internship_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_internships');
    }
};
