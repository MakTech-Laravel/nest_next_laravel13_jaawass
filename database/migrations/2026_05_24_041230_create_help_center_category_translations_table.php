<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('help_center_category_translations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('locale')->index();

            $table->foreignId('help_center_category_id')->constrained(
                table: 'help_center_categories',
                indexName: 'hc_category_fk'
            )->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['help_center_category_id', 'locale'],
                'hc_category_locale_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_center_category_translations');
    }
};
