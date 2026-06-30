<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('about_pages')) {
            Schema::create('about_pages', function (Blueprint $table): void {
                $table->id();
                $table->boolean('enabled')->default(true);
                $table->json('content');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('about_page_translations')) {
            Schema::create('about_page_translations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('about_page_id')->constrained('about_pages')->cascadeOnDelete();
                $table->string('locale', 10);
                $table->json('content');
                $table->timestamps();

                $table->unique(['about_page_id', 'locale'], 'about_page_locale_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('about_page_translations');
        Schema::dropIfExists('about_pages');
    }
};
