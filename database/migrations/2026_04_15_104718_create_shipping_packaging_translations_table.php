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
        Schema::create('shipping_packaging_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_packaging_id')->constrained('shipping_packagings')->cascadeOnDelete();
            $table->string('locale')->index();

            $table->string('packaging_type');
            $table->string('port_of_loading');
            $table->string('packaging_dimensions');
            $table->string('packaging_weight');
            $table->string('packaging_description');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_packaging_translations');
    }
};
