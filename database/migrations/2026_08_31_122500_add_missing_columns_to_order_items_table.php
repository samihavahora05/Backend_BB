<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('order_items', 'course_id')) {
                    $table->unsignedBigInteger('course_id')->nullable();
                }
                if (!Schema::hasColumn('order_items', 'purchasable_type')) {
                    $table->string('purchasable_type')->nullable();
                }
                if (!Schema::hasColumn('order_items', 'purchasable_id')) {
                    $table->unsignedBigInteger('purchasable_id')->nullable();
                }
                if (!Schema::hasColumn('order_items', 'item_type')) {
                    $table->string('item_type')->nullable();
                }
                if (!Schema::hasColumn('order_items', 'item_id')) {
                    $table->unsignedBigInteger('item_id')->nullable();
                }
                if (!Schema::hasColumn('order_items', 'price')) {
                    $table->decimal('price', 10, 2)->default(0);
                }
                if (!Schema::hasColumn('order_items', 'quantity')) {
                    $table->integer('quantity')->default(1);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                $columns = ['course_id', 'purchasable_type', 'purchasable_id', 'item_type', 'item_id'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('order_items', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
