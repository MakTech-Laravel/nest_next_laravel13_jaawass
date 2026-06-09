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
        Schema::create('subscription_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manufacturer_id');
            $table->unsignedBigInteger('from_plan_id')->nullable();
            $table->unsignedBigInteger('to_plan_id');
            $table->string('event_type');
            $table->decimal('paid_amount', 10, 2)->nullable();
            $table->timestamps();
            
            $table->foreign('manufacturer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('from_plan_id')->references('id')->on('plans')->cascadeOnDelete();
            $table->foreign('to_plan_id')->references('id')->on('plans')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_logs');
    }
};
