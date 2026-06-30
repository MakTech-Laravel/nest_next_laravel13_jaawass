<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('legal_pages')) {
            Schema::create('legal_pages', function (Blueprint $table): void {
                $table->id();
                $table->string('slug', 64)->unique();
                $table->string('title');
                $table->string('last_updated_label')->nullable();
                $table->boolean('enabled')->default(true);
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('legal_page_translations')) {
            Schema::create('legal_page_translations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('legal_page_id')->constrained('legal_pages')->cascadeOnDelete();
                $table->string('locale', 10);
                $table->string('title');
                $table->timestamps();

                $table->unique(['legal_page_id', 'locale'], 'legal_page_locale_unique');
            });
        }

        if (! Schema::hasTable('legal_page_sections')) {
            Schema::create('legal_page_sections', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('legal_page_id')->constrained('legal_pages')->cascadeOnDelete();
                $table->string('section_key', 128);
                $table->string('title');
                $table->longText('content');
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();

                $table->unique(['legal_page_id', 'section_key'], 'legal_page_section_key_unique');
            });
        }

        if (! Schema::hasTable('legal_page_section_translations')) {
            Schema::create('legal_page_section_translations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('legal_page_section_id')->constrained('legal_page_sections')->cascadeOnDelete();
                $table->string('locale', 10);
                $table->string('title');
                $table->longText('content');
                $table->timestamps();

                $table->unique(['legal_page_section_id', 'locale'], 'legal_page_section_locale_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_page_section_translations');
        Schema::dropIfExists('legal_page_sections');
        Schema::dropIfExists('legal_page_translations');
        Schema::dropIfExists('legal_pages');
    }
};
