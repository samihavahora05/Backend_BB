<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('system_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key')->unique();
            $table->string('domain')->nullable();
            $table->string('email')->nullable();
            $table->enum('status', ['active', 'suspended', 'expired'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('system_email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subject');
            $table->longText('body');
            $table->json('variables')->nullable(); // e.g., ["{user_name}", "{reset_link}"]
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('system_api_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique(); // e.g., 'google', 'stripe', 'openai'
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->boolean('status')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('system_api_credentials');
        Schema::dropIfExists('system_email_templates');
        Schema::dropIfExists('system_licenses');
    }
};
