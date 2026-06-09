<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rfq_submissions', function (Blueprint $table) {
            $table->text('manufacturer_reply')->nullable()->after('additional_requirements');
            $table->decimal('quoted_price', 12, 2)->nullable()->after('manufacturer_reply');
            $table->string('quote_currency_code', 3)->nullable()->after('quoted_price');
            $table->unsignedInteger('minimum_order_quantity')->nullable()->after('quote_currency_code');
            $table->unsignedInteger('lead_time_days')->nullable()->after('minimum_order_quantity');
            $table->date('quote_valid_until')->nullable()->after('lead_time_days');
            $table->timestamp('quoted_at')->nullable()->after('quote_valid_until');
            $table->timestamp('buyer_action_at')->nullable()->after('quoted_at');
            $table->index(['status', 'quote_valid_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfq_submissions', function (Blueprint $table) {
            $table->dropIndex(['status', 'quote_valid_until']);
            $table->dropColumn([
                'manufacturer_reply',
                'quoted_price',
                'quote_currency_code',
                'minimum_order_quantity',
                'lead_time_days',
                'quote_valid_until',
                'quoted_at',
                'buyer_action_at',
            ]);
        });
    }
};
