<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('help_center_article_translations', function (Blueprint $table) {
            $table->id();

            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('locale')->index();

            $table->unsignedBigInteger('help_center_article_id');

            $table
                ->foreign(
                    'help_center_article_id',
                    'hc_article_fk'
                )
                ->references('id')
                ->on('help_center_articles')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['help_center_article_id', 'locale'],
                'hc_article_locale_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_center_article_translations');
    }
};
