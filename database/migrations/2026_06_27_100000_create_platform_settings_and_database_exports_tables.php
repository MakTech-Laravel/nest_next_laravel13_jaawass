<?php

use App\Enums\DatabaseExportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 64)->unique();
            $table->json('payload');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('database_exports', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 32);
            $table->string('scope', 32);
            $table->json('tables')->nullable();
            $table->unsignedInteger('chunk_rows')->default(1000);
            $table->string('status', 32)->default(DatabaseExportStatus::Pending->value);
            $table->unsignedSmallInteger('total_tables')->default(0);
            $table->unsignedSmallInteger('processed_tables')->default(0);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedSmallInteger('total_parts')->default(0);
            $table->string('storage_path')->nullable();
            $table->string('download_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_exports');
        Schema::dropIfExists('platform_settings');
    }
};
