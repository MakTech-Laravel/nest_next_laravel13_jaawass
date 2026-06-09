<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Cleanup duplicated rows created before we had a unique
     * (faq_id, locale) constraint on `faq_translations`.
     */
    public function up(): void
    {
        $duplicates = DB::table('faq_translations')
            ->select('faq_id', 'locale', DB::raw('COUNT(*) AS cnt'))
            ->groupBy('faq_id', 'locale')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            // Keep the newest row so it matches the latest update attempt.
            $keepId = DB::table('faq_translations')
                ->where('faq_id', $dup->faq_id)
                ->where('locale', $dup->locale)
                ->orderByDesc('id')
                ->value('id');

            if ($keepId === null) {
                continue;
            }

            DB::table('faq_translations')
                ->where('faq_id', $dup->faq_id)
                ->where('locale', $dup->locale)
                ->where('id', '!=', $keepId)
                ->delete();
        }
    }

    public function down(): void
    {
        // Intentionally left blank.
    }
};
