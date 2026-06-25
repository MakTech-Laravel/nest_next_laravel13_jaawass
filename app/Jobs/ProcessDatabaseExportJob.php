<?php

namespace App\Jobs;

use App\Models\DatabaseExport;
use App\Services\Database\DatabaseExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessDatabaseExportJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public function __construct(
        public int $exportId,
    ) {}

    public function handle(DatabaseExportService $exportService): void
    {
        $export = DatabaseExport::query()->findOrFail($this->exportId);
        $exportService->process($export);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ProcessDatabaseExportJob failed.', [
            'export_id' => $this->exportId,
            'error' => $exception->getMessage(),
        ]);

        DatabaseExport::query()
            ->whereKey($this->exportId)
            ->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
    }
}
