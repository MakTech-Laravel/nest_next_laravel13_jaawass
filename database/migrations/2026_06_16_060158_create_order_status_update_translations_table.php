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
        Schema::create('order_status_update_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_status_update_id')->constrained('order_status_updates')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('locale');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['order_status_update_id', 'locale'], 'order_status_upd_trans_locale_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_update_translations');
    }
};
