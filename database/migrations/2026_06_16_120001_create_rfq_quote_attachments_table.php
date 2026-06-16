<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfq_quote_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_submission_id')->constrained('rfq_submissions')->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('disk', 32)->default('public');
            $table->string('file_path');
            $table->string('file_mime', 128)->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();

            $table->index(['rfq_submission_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_quote_attachments');
    }
};
