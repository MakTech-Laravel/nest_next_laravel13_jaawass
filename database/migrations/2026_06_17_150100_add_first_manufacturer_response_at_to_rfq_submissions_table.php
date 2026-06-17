<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rfq_submissions', function (Blueprint $table): void {
            $table->dateTime('first_manufacturer_response_at')->nullable()->after('quoted_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('rfq_submissions', function (Blueprint $table): void {
            $table->dropColumn('first_manufacturer_response_at');
        });
    }
};
