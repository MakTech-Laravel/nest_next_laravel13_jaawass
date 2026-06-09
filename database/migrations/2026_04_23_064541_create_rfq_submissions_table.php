<?php

use App\Enums\RfqSubmissionStatus;
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
        Schema::create('rfq_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_number', 32)->nullable()->unique();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('manufacturer_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete()->cascadeOnUpdate();
            $table->unsignedInteger('quantity');
            $table->string('quantity_unit', 64)->default('pieces');
            $table->decimal('target_price', 12, 2)->nullable();
            $table->string('target_currency_code', 3)->nullable();
            $table->date('required_delivery_date')->nullable();
            $table->string('shipping_terms', 64)->nullable();
            $table->string('destination_country', 128)->nullable();
            $table->string('destination_port_city', 128)->nullable();
            $table->string('packaging_details', 128)->nullable();
            $table->text('additional_requirements')->nullable();
            $table->string('status')->default(RfqSubmissionStatus::Pending->value);
            $table->timestamps();

            $table->index(['buyer_id', 'created_at']);
            $table->index(['manufacturer_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfq_submissions');
    }
};
