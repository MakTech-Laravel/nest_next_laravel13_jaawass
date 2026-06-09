<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plans') || Schema::hasColumn('plans', 'currency_id')) {
            return;
        }

        Schema::table('plans', function (Blueprint $table) {
            $table->foreignId('currency_id')->nullable()->after('id')->constrained('currencies')->restrictOnDelete();
        });

        $baseCode = strtoupper((string) env('APP_BASE_CURRENCY', 'USD'));
        $baseId = (int) DB::table('currencies')->where('code', $baseCode)->value('id');
        if ($baseId === 0) {
            $baseId = (int) DB::table('currencies')->where('code', 'USD')->value('id');
        }

        if ($baseId !== 0) {
            DB::table('plans')->whereNull('currency_id')->update(['currency_id' => $baseId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('plans') && Schema::hasColumn('plans', 'currency_id')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->dropConstrainedForeignId('currency_id');
            });
        }
    }
};
