<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_center_article_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_center_article_id')
                ->constrained('help_center_articles')
                ->cascadeOnDelete();
            $table->text('content');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_center_article_steps');
    }
};
