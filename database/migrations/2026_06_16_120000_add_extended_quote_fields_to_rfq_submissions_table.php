<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfq_submissions', function (Blueprint $table) {
            $table->string('lead_time', 128)->nullable()->after('lead_time_days');
            $table->string('quote_shipping_terms', 64)->nullable()->after('lead_time');
            $table->string('quote_payment_terms', 255)->nullable()->after('quote_shipping_terms');
            $table->string('sample_cost', 128)->nullable()->after('quote_payment_terms');
            $table->string('sample_lead_time', 128)->nullable()->after('sample_cost');
            $table->text('quote_packaging_details')->nullable()->after('sample_lead_time');
            $table->json('quote_certifications')->nullable()->after('quote_packaging_details');
            $table->text('quote_notes')->nullable()->after('quote_certifications');
        });
    }

    public function down(): void
    {
        Schema::table('rfq_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'lead_time',
                'quote_shipping_terms',
                'quote_payment_terms',
                'sample_cost',
                'sample_lead_time',
                'quote_packaging_details',
                'quote_certifications',
                'quote_notes',
            ]);
        });
    }
};
