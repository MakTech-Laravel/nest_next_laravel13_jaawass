<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'preferred_language')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('preferred_language', 24)->default('en')->after('preferred_currency_id');
            });
        }

        DB::table('users')->whereNull('preferred_language')->update(['preferred_language' => 'en']);

        if (Schema::hasTable('currencies')) {
            $usdId = DB::table('currencies')->where('code', 'USD')->value('id');
            if ($usdId !== null) {
                DB::table('users')->whereNull('preferred_currency_id')->update(['preferred_currency_id' => $usdId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'preferred_language')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('preferred_language');
            });
        }
    }
};
