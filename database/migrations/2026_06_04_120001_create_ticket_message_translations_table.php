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
        Schema::create('ticket_message_translations', function (Blueprint $table) {
            $table->id();
            $table->text('message')->nullable();
            $table->string('locale')->index();
            $table->unsignedBigInteger('ticket_message_id');

            $table->foreign('ticket_message_id')
                ->references('id')
                ->on('ticket_messages')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['ticket_message_id', 'locale'], 'ticket_message_locale_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_message_translations');
    }
};
