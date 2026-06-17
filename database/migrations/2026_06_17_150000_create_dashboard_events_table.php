<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('counterparty_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role_context', 32)->default('system')->index();
            $table->string('event_type', 64)->index();
            $table->string('entity_type', 64)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at')->index();
            $table->timestamps();

            $table->index(['event_type', 'occurred_at']);
            $table->index(['actor_user_id', 'occurred_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_events');
    }
};
