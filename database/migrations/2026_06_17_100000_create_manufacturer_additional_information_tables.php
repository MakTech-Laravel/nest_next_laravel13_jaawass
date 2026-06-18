<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturer_additional_information_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('requested_by');
            $table->string('token', 64)->unique();
            $table->text('message');
            $table->json('allowed_types');
            $table->string('status', 32)->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('requested_by', 'mfg_add_info_req_requested_by_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index(['user_id', 'status'], 'mfg_add_info_req_user_status_idx');
        });

        Schema::create('manufacturer_additional_information_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->string('type', 32);
            $table->text('message')->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->foreign('request_id', 'mfg_add_info_resp_request_fk')
                ->references('id')
                ->on('manufacturer_additional_information_requests')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturer_additional_information_responses');
        Schema::dropIfExists('manufacturer_additional_information_requests');
    }
};
