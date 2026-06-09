<?php

use App\Enums\Api\V1\ProductStatusEnum;
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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();

            // Categories & Sub Categories

            $table->unsignedBigInteger('industry_id')->index()->comment('Also Know as Category');
            $table->unsignedBigInteger('sub_category_id')->index();

            $table->integer('view_count')->default(0);
            $table->unsignedInteger('inquiry_count')->default(0);
            $table->json('keywords')->nullable();

            $table->string('status')->default(ProductStatusEnum::DRAFT->value);

            $table->boolean('is_approved')->default(false);
            // Relations
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->foreign('industry_id')->references('id')->on('industries')->onDelete('cascade');
            $table->foreign('sub_category_id')->references('id')->on('sub_categories')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
