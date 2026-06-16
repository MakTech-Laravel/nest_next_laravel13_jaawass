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
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('order_id')
                ->nullable()
                ->after('product_id')
                ->constrained('orders')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('title')->nullable()->after('rating');
            $table->text('comment')->nullable()->after('title');
            $table->unique(['reviewer_id', 'order_id'], 'reviews_reviewer_order_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_reviewer_order_unique');
            $table->dropConstrainedForeignId('order_id');
            $table->dropColumn(['title', 'comment']);
        });
    }
};
