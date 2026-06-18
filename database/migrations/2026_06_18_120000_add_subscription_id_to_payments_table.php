<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('subscription_id')->nullable()->after('user_id');

            $table->foreign('subscription_id', 'payments_subscription_id_fk')
                ->references('id')
                ->on('subscriptions')
                ->nullOnDelete();

            $table->unique('payment_id', 'payments_payment_id_unique');
            $table->index(['payment_method', 'created_at'], 'payments_method_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign('payments_subscription_id_fk');
            $table->dropUnique('payments_payment_id_unique');
            $table->dropIndex('payments_method_created_idx');
            $table->dropColumn('subscription_id');
        });
    }
};
