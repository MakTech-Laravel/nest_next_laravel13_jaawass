<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();

            // BCP-47 locale code: "en", "ar", "zh-CN"
            $table->string('locale', 10)->unique();

            // English name
            $table->string('name', 100);

            // Name in the language itself ("العربية", "中文")
            $table->string('native_name', 100);

            // ISO 3166-1 alpha-2 for flag hints
            $table->string('country_code', 5)->nullable();

            $table->boolean('is_rtl')->default(false);

            // Controls whether new content is auto-translated into this locale
            $table->boolean('is_active')->default(true);

            // Exactly one row should have this true — the source language
            $table->boolean('is_default')->default(false);

            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
