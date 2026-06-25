<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\DatabaseExportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreDatabaseExportRequest;
use App\Http\Resources\Api\V1\Admin\DatabaseExportResource;
use App\Models\DatabaseExport;
use App\Services\Database\DatabaseExportService;
use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class AdminDatabaseExportController extends Controller
{
    public function __construct(
        private readonly DatabaseExportService $exportService,
    ) {}

    public function tables()
    {
        return sendResponse(
            status: true,
            message: __('database_export.tables_success'),
            data: $this->exportService->listTables(),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function index(Request $request)
    {
        $exports = DatabaseExport::query()
            ->with('creator')
            ->latest('id')
            ->paginate($request->integer('per_page', 10));

        return sendResponse(
            status: true,
            message: __('database_export.list_success'),
            data: DatabaseExportResource::collection($exports),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function store(StoreDatabaseExportRequest $request)
    {
        try {
            $export = $this->exportService->create(
                $request->user(),
                $request->validated(),
            );
        } catch (\InvalidArgumentException $exception) {
            return sendResponse(
                status: false,
                message: $exception->getMessage(),
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return sendResponse(
            status: true,
            message: __('database_export.created'),
            data: new DatabaseExportResource($export),
            statusCode: HttpStatus::HTTP_ACCEPTED,
        );
    }

    public function show(DatabaseExport $databaseExport)
    {
        $databaseExport->load('creator');

        return sendResponse(
            status: true,
            message: __('database_export.show_success'),
            data: new DatabaseExportResource($databaseExport),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function download(DatabaseExport $databaseExport)
    {
        if ($databaseExport->status !== DatabaseExportStatus::Completed || $databaseExport->storage_path === null) {
            return sendResponse(
                status: false,
                message: __('database_export.not_ready'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        if (! Storage::disk('local')->exists($databaseExport->storage_path)) {
            return sendResponse(
                status: false,
                message: __('database_export.file_missing'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        return Storage::disk('local')->download(
            $databaseExport->storage_path,
            $databaseExport->download_name ?? 'database-export.zip',
        );
    }

    public function backupStatus()
    {
        $latest = $this->exportService->latestCompletedBackup();

        return sendResponse(
            status: true,
            message: __('database_export.backup_status_success'),
            data: [
                'last_backup_at' => $latest ? TimezoneFormatter::format($latest->completed_at) : null,
                'last_backup' => $latest ? new DatabaseExportResource($latest) : null,
            ],
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}
