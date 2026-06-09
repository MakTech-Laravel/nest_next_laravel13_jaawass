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
        Schema::create('product_specification_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_specification_id');
            $table->string('locale');
            $table->string('specification_title');
            $table->string('specification_value');
            $table->foreign('product_specification_id', 'pst_ps_id_fk')
                ->references('id')
                ->on('product_specifications')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_specification_translations');
    }
};
