<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (Schema::hasColumn('quizzes', 'lesson_id')) {
                $table->foreignId('virtual_class_id')->nullable()->after('lesson_id')->constrained('virtual_classes')->nullOnDelete();
            } else {
                $table->foreignId('virtual_class_id')->nullable()->constrained('virtual_classes')->nullOnDelete();
            }

            if (!Schema::hasColumn('quizzes', 'passing_score')) {
                $table->unsignedTinyInteger('passing_score')->default(70)->after('title');
            }
        });

        // Virtual-class quizzes do not belong to a course lesson.
        if (Schema::hasColumn('quizzes', 'lesson_id')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropForeign(['lesson_id']);
                $table->foreignId('lesson_id')->nullable()->change();
                $table->foreign('lesson_id')->references('id')->on('lessons')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quizzes')) {
            Schema::table('quizzes', function (Blueprint $table) {
                if (Schema::hasColumn('quizzes', 'virtual_class_id')) {
                    $table->dropForeign(['virtual_class_id']);
                    $table->dropColumn('virtual_class_id');
                }
                if (Schema::hasColumn('quizzes', 'passing_score')) {
                    $table->dropColumn('passing_score');
                }
            });
        }
    }
};
