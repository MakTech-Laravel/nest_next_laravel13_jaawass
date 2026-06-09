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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->unique();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('company_name')->nullable();
           
            $table->string('short_description')->nullable();
            $table->text('long_description')->nullable();
            $table->integer('minimum_order_value')->nullable();
            $table->string('company_logo')->nullable();

            $table->string('company_type')->nullable()->comment('Type of company (e.g., manufacturer, distributor, etc.)');
            $table->string('company_established')->nullable()->comment('Year company was established');
            $table->string('company_size')->nullable()->comment('Size of company (e.g., small, medium, large)');
            $table->string('revenue')->nullable()->comment('Company revenue (e.g., low, medium, high)');
           
           
             $table->string('country')->nullable()->index();
             $table->string('city')->nullable()->index();
             $table->string('street_address')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('zip_code')->nullable()->index();

            // Industries will be Pivot Table  //

            $table->json('capabilities')->nullable();
            $table->json('certifications')->nullable();
            $table->json('export_markets')->nullable();
            $table->json('language_spoken')->nullable();
            $table->json('payments_term')->nullable();


            $table->boolean('factory_production')->default(false);
            $table->boolean('mulitple_factories')->default(false);

            $table->string('bussiness_license')->nullable()->comment('Business license file path (pdf, jpg, png, etc.)');
            $table->string('company_website')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_information');
    }
};
