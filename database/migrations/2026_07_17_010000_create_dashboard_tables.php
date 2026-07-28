<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dashboard_widgets')) {
            Schema::create('dashboard_widgets', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('key')->unique();
                $table->string('type'); // chart, counter, list
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('dashboard_preferences')) {
            Schema::create('dashboard_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
                $table->json('layout'); // Widget positions & sizes
                $table->json('hidden_widgets')->nullable();
                $table->timestamps();
                $table->unique('admin_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_preferences');
        Schema::dropIfExists('dashboard_widgets');
    }
};
