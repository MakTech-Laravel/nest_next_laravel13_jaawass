<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faq_translations', function (Blueprint $table) {
            $table->unique(['faq_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::table('faq_translations', function (Blueprint $table) {
            $table->dropUnique(['faq_id', 'locale']);
        });
    }
};
