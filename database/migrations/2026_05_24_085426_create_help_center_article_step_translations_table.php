<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('help_center_article_step_translations', function (Blueprint $table) {
            $table->id();

            $table->text('content')->nullable();
            $table->string('locale')->index();

            $table->unsignedBigInteger('help_center_article_step_id');

            $table
                ->foreign(
                    'help_center_article_step_id',
                    'hc_article_step_fk'
                )
                ->references('id')
                ->on('help_center_article_steps')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['help_center_article_step_id', 'locale'],
                'hc_article_step_locale_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_center_article_step_translations');
    }
};
