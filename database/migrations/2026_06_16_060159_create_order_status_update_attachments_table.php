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
        Schema::create('order_status_update_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_status_update_id')->constrained('order_status_updates')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('type', 32)->default('file');
            $table->string('disk')->default('public');
            $table->string('file_path');
            $table->string('file_mime')->nullable();
            $table->string('original_name');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamps();

            $table->index('order_status_update_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_update_attachments');
    }
};
