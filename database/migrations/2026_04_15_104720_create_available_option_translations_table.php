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
        Schema::create('available_option_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('available_option_id');
            $table->string('customization_detail');
            $table->string('locale');
            $table->foreign('available_option_id')->references('id')->on('available_options')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('available_option_translations');
    }
};
