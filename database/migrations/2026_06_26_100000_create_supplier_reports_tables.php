<?php

use App\Enums\SupplierReportPriority;
use App\Enums\SupplierReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 32);
            $table->text('details')->nullable();
            $table->string('status', 32)->default(SupplierReportStatus::Open->value);
            $table->string('priority', 16)->default(SupplierReportPriority::Medium->value);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('source_page')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'status', 'created_at']);
            $table->index(['reporter_id', 'supplier_id', 'created_at']);
            $table->index(['status', 'priority', 'created_at']);
            $table->index(['assigned_to', 'status']);
        });

        Schema::create('supplier_report_status_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_report_id')->constrained('supplier_reports')->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['supplier_report_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_report_status_logs');
        Schema::dropIfExists('supplier_reports');
    }
};
