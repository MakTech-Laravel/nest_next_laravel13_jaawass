<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureCurrenciesExist();

        $baseCode = strtoupper((string) env('APP_BASE_CURRENCY', 'USD'));
        $baseId = (int) DB::table('currencies')->where('code', $baseCode)->value('id');
        if ($baseId === 0) {
            $baseId = (int) DB::table('currencies')->where('code', 'USD')->value('id');
        }

        if (! Schema::hasColumn('users', 'preferred_currency_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('preferred_currency_id')->nullable()->after('remember_token')->constrained('currencies')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('products', 'currency_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('currency_id')->nullable()->after('user_id')->constrained('currencies')->restrictOnDelete();
            });
        }

        if (Schema::hasTable('plans') && ! Schema::hasColumn('plans', 'currency_id')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->foreignId('currency_id')->nullable()->after('id')->constrained('currencies')->restrictOnDelete();
            });
        }

        if (Schema::hasColumn('products', 'currency_id')) {
            DB::table('products')->whereNull('currency_id')->update(['currency_id' => $baseId]);
        }

        if (Schema::hasTable('plans') && Schema::hasColumn('plans', 'currency_id')) {
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

        if (Schema::hasColumn('products', 'currency_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('currency_id');
            });
        }

        if (Schema::hasColumn('users', 'preferred_currency_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('preferred_currency_id');
            });
        }
    }

    private function ensureCurrenciesExist(): void
    {
        $rows = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'is_active' => true, 'sort_order' => 0],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2, 'is_active' => true, 'sort_order' => 1],
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => 'SR', 'decimal_places' => 2, 'is_active' => true, 'sort_order' => 2],
        ];

        $now = now();

        foreach ($rows as $row) {
            $exists = DB::table('currencies')->where('code', $row['code'])->exists();
            if (! $exists) {
                DB::table('currencies')->insert([
                    ...$row,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
};
