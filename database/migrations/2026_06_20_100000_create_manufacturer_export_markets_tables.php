<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturer_export_markets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('region', 120);
            $table->timestamps();

            $table->unique(['user_id', 'region']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('manufacturer_export_market_countries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('manufacturer_export_market_id')
                ->constrained('manufacturer_export_markets')
                ->cascadeOnDelete();
            $table->string('country_code', 8);
            $table->string('country_name', 120);
            $table->timestamps();

            $table->unique(['manufacturer_export_market_id', 'country_code'], 'manufacturer_export_market_countries_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturer_export_market_countries');
        Schema::dropIfExists('manufacturer_export_markets');
    }
};
