<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expert_profiles', function (Blueprint $table) {
            $table->index(['is_available', 'average_rating'], 'idx_avail_rating');
            $table->index(['is_available', 'hourly_rate'], 'idx_avail_hourly');
            $table->index('experience_years', 'idx_exp_years');
        });
    }

    public function down(): void
    {
        Schema::table('expert_profiles', function (Blueprint $table) {
            $table->dropIndex('idx_avail_rating');
            $table->dropIndex('idx_avail_hourly');
            $table->dropIndex('idx_exp_years');
        });
    }
};