<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('expert_profiles', 'profile_photo')) {
            Schema::table('expert_profiles', function (Blueprint $table) {
                $table->string('profile_photo')->nullable()->after('specialization');
            });
        }
    }

    public function down(): void
    {
        Schema::table('expert_profiles', function (Blueprint $table) {
            $table->dropColumn('profile_photo');
        });
    }
};
