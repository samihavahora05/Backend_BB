<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Industries
        Schema::create('cms_industries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Companies (also used for Hiring Partners)
        Schema::create('cms_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_url')->nullable();
            $table->foreignId('industry_id')->nullable()->constrained('cms_industries')->nullOnDelete();
            $table->string('website_url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('show_on_homepage')->default(false);
            $table->integer('display_order')->default(0);
            $table->enum('status', ['published', 'draft', 'archived'])->default('published');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
        });

        // 3. Placement Partners (Specific type of partner)
        Schema::create('cms_placement_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_url')->nullable();
            $table->foreignId('industry_id')->nullable()->constrained('cms_industries')->nullOnDelete();
            $table->string('website_url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('show_on_homepage')->default(false);
            $table->integer('display_order')->default(0);
            $table->enum('status', ['published', 'draft', 'archived'])->default('published');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
        });

        // 4. Colleges
        Schema::create('cms_colleges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_url')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->integer('display_order')->default(0);
            $table->enum('status', ['published', 'draft', 'archived'])->default('published');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
        });

        // 5. Project Portfolios
        Schema::create('cms_portfolios', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('studio')->nullable(); // Company or studio name
            $table->string('category')->nullable(); // e.g. '3D ANIMATION'
            $table->text('description')->nullable();
            $table->string('duration')->nullable(); // e.g. '8 WEEKS'
            $table->string('deliverables')->nullable();
            $table->string('image_url')->nullable();
            $table->string('link')->nullable();
            $table->json('tags')->nullable(); // JSON array of tags
            $table->boolean('is_featured')->default(false);
            $table->integer('display_order')->default(0);
            $table->enum('status', ['published', 'draft', 'archived'])->default('published');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
        });
        
        // Enhance testimonials
        if (Schema::hasTable('testimonials')) {
            Schema::table('testimonials', function (Blueprint $table) {
                if (!Schema::hasColumn('testimonials', 'status')) {
                    $table->enum('status', ['published', 'draft', 'archived'])->default('published');
                }
                if (!Schema::hasColumn('testimonials', 'display_order')) {
                    $table->integer('display_order')->default(0);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_portfolios');
        Schema::dropIfExists('cms_colleges');
        Schema::dropIfExists('cms_placement_partners');
        Schema::dropIfExists('cms_companies');
        Schema::dropIfExists('cms_industries');
        
        if (Schema::hasTable('testimonials')) {
            Schema::table('testimonials', function (Blueprint $table) {
                if (Schema::hasColumn('testimonials', 'status')) {
                    $table->dropColumn('status');
                }
                if (Schema::hasColumn('testimonials', 'display_order')) {
                    $table->dropColumn('display_order');
                }
            });
        }
    }
};
