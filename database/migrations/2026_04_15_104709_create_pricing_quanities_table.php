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
        Schema::create('pricing_quanities', function (Blueprint $table) {
            $table->id();
            $table->double('min_price', 10, 2);
            $table->double('max_price', 10, 2);
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->integer('minimum_order_quantity');
            $table->string('unit');
            $table->string('lead_time');
            $table->integer('production_capacity');
            $table->string('production_duration');
            $table->string('production_unit');


            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('currency_id')->references('id')->on('currencies')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_quanities');
    }
};
