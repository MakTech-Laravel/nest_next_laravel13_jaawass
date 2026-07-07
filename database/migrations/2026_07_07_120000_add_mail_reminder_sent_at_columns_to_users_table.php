<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('buyer_registration_reminder_sent_at')->nullable()->after('email_verified_at');
            $table->timestamp('manufacturer_registration_reminder_sent_at')->nullable()->after('buyer_registration_reminder_sent_at');
            $table->timestamp('manufacturer_activation_reminder_sent_at')->nullable()->after('manufacturer_registration_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'buyer_registration_reminder_sent_at',
                'manufacturer_registration_reminder_sent_at',
                'manufacturer_activation_reminder_sent_at',
            ]);
        });
    }
};
