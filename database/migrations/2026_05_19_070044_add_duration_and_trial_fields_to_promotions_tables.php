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
        Schema::table('promotions', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_months')->default(6)->after('slots');
        });

        Schema::table('promotion_user', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('participated_at');
            $table->unique(['promotion_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotion_user', function (Blueprint $table) {
            $table->dropUnique(['promotion_id', 'user_id']);
            $table->dropColumn('trial_ends_at');
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('duration_months');
        });
    }
};
