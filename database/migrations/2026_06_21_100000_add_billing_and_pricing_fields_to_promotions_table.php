<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table): void {
            $table->decimal('promotional_price', 10, 2)->default(0)->after('duration_months');
            $table->boolean('requires_payment')->default(false)->after('promotional_price');
            $table->string('billing_period_unit', 10)->default('month')->after('requires_payment');
            $table->text('disclaimer_text')->nullable()->after('highlight_text');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table): void {
            $table->dropColumn([
                'promotional_price',
                'requires_payment',
                'billing_period_unit',
                'disclaimer_text',
            ]);
        });
    }
};
