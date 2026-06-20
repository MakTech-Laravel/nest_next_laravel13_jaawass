<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dashboard_events', function (Blueprint $table): void {
            $table->index(
                ['counterparty_user_id', 'event_type', 'occurred_at'],
                'dashboard_events_counterparty_type_occurred_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_events', function (Blueprint $table): void {
            $table->dropIndex('dashboard_events_counterparty_type_occurred_index');
        });
    }
};
