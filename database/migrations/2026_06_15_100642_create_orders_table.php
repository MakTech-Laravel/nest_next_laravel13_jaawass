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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('manufacturer_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('title');
            $table->unsignedInteger('quantity');
            $table->string('quantity_unit', 64)->default('pieces');
            $table->decimal('total_amount', 12, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->date('estimated_delivery_at');
            $table->string('production_lead', 128)->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('shipping_terms', 128)->nullable();
            $table->string('destination', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['buyer_id', 'created_at']);
            $table->index(['manufacturer_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
