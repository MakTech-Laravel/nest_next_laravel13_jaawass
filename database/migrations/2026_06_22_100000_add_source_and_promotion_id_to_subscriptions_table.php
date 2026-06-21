<?php

use App\Enums\Api\V1\SubscriptionSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->string('source', 20)
                ->default(SubscriptionSource::PURCHASE->value)
                ->after('auto_renew');
            $table->unsignedBigInteger('promotion_id')
                ->nullable()
                ->after('source');

            $table->foreign('promotion_id')
                ->references('id')
                ->on('promotions')
                ->nullOnDelete();

            $table->unique('manufacturer_id');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropUnique(['manufacturer_id']);
            $table->dropForeign(['promotion_id']);
            $table->dropColumn(['source', 'promotion_id']);
        });
    }
};
