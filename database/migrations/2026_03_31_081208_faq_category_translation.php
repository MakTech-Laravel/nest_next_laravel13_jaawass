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
        //
        Schema::create('faq_category_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faq_category_id')->index();
            $table->foreign('faq_category_id')->references('id')->on('faq_categories')->onDelete('cascade');

            $table->string('name')->nullable();
            $table->string('locale')->index()->default('en');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
