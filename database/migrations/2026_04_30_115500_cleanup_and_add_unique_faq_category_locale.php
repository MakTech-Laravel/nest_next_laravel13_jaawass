<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove duplicate faq_category_translations rows and enforce one row per locale.
     */
    public function up(): void
    {
        $duplicates = DB::table('faq_category_translations')
            ->select('faq_category_id', 'locale', DB::raw('COUNT(*) AS cnt'))
            ->groupBy('faq_category_id', 'locale')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $keepId = DB::table('faq_category_translations')
                ->where('faq_category_id', $dup->faq_category_id)
                ->where('locale', $dup->locale)
                ->orderByDesc('id')
                ->value('id');

            if ($keepId === null) {
                continue;
            }

            DB::table('faq_category_translations')
                ->where('faq_category_id', $dup->faq_category_id)
                ->where('locale', $dup->locale)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        Schema::table('faq_category_translations', function (Blueprint $table): void {
            $table->unique(
                ['faq_category_id', 'locale'],
                'faq_category_translations_faq_category_id_locale_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('faq_category_translations', function (Blueprint $table): void {
            $table->dropUnique('faq_category_translations_faq_category_id_locale_unique');
        });
    }
};

