<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Single table: all quick-filter rows (Countries, Certifications, MOQ Ranges, Export Markets)
     * distinguished by `type`.
     */
    public function up(): void
    {
        Schema::create('quick_filter_options', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32)->index();
            $table->string('display_label');
            $table->string('value', 191);
            $table->boolean('is_enabled')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();

            $table->unique(['type', 'value']);
            $table->index(['type', 'sort_order', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_filter_options');
    }
};
