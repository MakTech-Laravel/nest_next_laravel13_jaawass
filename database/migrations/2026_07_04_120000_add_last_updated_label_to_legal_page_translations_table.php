<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('legal_page_translations')) {
            return;
        }

        Schema::table('legal_page_translations', function (Blueprint $table): void {
            if (! Schema::hasColumn('legal_page_translations', 'last_updated_label')) {
                $table->string('last_updated_label')->nullable()->after('title');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('legal_page_translations')) {
            return;
        }

        Schema::table('legal_page_translations', function (Blueprint $table): void {
            if (Schema::hasColumn('legal_page_translations', 'last_updated_label')) {
                $table->dropColumn('last_updated_label');
            }
        });
    }
};
