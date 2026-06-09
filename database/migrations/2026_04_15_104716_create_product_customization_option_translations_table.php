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
        Schema::create('product_customization_option_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_customization_option_id');

            $table->string('locale');
            $table->string('option');

            $table->foreign('product_customization_option_id', 'pco_option_id_fk')
                ->references('id')
                ->on('product_customization_options')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_customization_option_translations');
    }
};
