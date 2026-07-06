<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manufacturer_additional_information_requests', function (Blueprint $table): void {
            $table->foreignId('reviewed_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('manufacturer_additional_information_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['reviewed_at', 'review_notes']);
        });
    }
};
