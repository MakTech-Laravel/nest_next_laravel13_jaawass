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
        Schema::create('company_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
           
            $table->string('company_name')->nullable();
            $table->string('company_type')->nullable();
            $table->string('company_established')->nullable();
            $table->string('company_size')->nullable();
            $table->string('revenue')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('street_address')->nullable();
            $table->string('phone')->nullable();
            $table->string('zip_code')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('certifications')->nullable();
            $table->json('export_markets')->nullable();
            $table->string('short_description')->nullable();
            $table->text('long_description')->nullable();
            $table->text('notes')->nullable();

            $table->string('locale')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   
        Schema::dropIfExists('company_translations');
    }
};
