<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manufacturer_additional_information_requests', function (Blueprint $table): void {
            $table->foreignId('ticket_id')
                ->nullable()
                ->after('requested_by')
                ->constrained('tickets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('manufacturer_additional_information_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('ticket_id');
        });
    }
};
