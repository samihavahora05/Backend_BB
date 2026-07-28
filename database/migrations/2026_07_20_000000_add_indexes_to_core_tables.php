<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('email');
            $table->index('first_name');
            $table->index('last_name');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->index('title');
            $table->index('status');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->index('title');
            $table->index('status');
        });
        
        Schema::table('internships', function (Blueprint $table) {
            $table->index('title');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['first_name']);
            $table->dropIndex(['last_name']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['title']);
            $table->dropIndex(['status']);
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex(['title']);
            $table->dropIndex(['status']);
        });
        
        Schema::table('internships', function (Blueprint $table) {
            $table->dropIndex(['title']);
            $table->dropIndex(['status']);
        });
    }
};
