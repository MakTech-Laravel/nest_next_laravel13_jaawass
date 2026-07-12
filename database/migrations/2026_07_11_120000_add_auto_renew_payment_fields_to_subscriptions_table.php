<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('payment_method', 32)->nullable()->after('auto_renew');
            $table->string('paypal_vault_id')->nullable()->after('payment_method');
            $table->string('paypal_payer_id')->nullable()->after('paypal_vault_id');
            $table->unsignedTinyInteger('renew_attempts')->default(0)->after('paypal_payer_id');
            $table->timestamp('last_renew_attempt_at')->nullable()->after('renew_attempts');
            $table->timestamp('last_renewed_at')->nullable()->after('last_renew_attempt_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'paypal_vault_id',
                'paypal_payer_id',
                'renew_attempts',
                'last_renew_attempt_at',
                'last_renewed_at',
            ]);
        });
    }
};
