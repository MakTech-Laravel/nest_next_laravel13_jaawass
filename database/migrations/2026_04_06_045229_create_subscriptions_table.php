<?php

use App\Enums\Api\V1\BillingInterval;
use App\Enums\Api\V1\SubscriptionStatus;
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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manufacturer_id');
            $table->unsignedBigInteger('plan_id');    
            $table->string('billing_interval')->default(BillingInterval::MONTH->value);
            $table->string('status')->default(SubscriptionStatus::ACTIVE->value);
            
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('ends_at')->nullable(); // For cancellations
            $table->timestamp('trial_ends_at')->nullable();
            
            $table->boolean('auto_renew')->default(true);

            // Foreign keys
            $table->foreign('manufacturer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
            
            $table->timestamps();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
