<?php

namespace App\Services\Database;

use App\Enums\DatabaseExportScope;
use App\Enums\DatabaseExportStatus;
use App\Enums\DatabaseExportType;
use App\Jobs\ProcessDatabaseExportJob;
use App\Models\DatabaseExport;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class DatabaseExportService
{
    public function __construct(
        private readonly DatabaseSqlExporter $sqlExporter,
    ) {}

    /**
     * @return list<string>
     */
    public function listTables(): array
    {
        return $this->sqlExporter->listTables();
    }

    /**
     * @param  array{
     *     type: string,
     *     scope: string,
     *     tables?: list<string>|null,
     *     chunk_rows?: int
     * }  $payload
     */
    public function create(User $admin, array $payload): DatabaseExport
    {
        $scope = DatabaseExportScope::from($payload['scope']);
        $tables = $scope === DatabaseExportScope::Tables
            ? $this->resolveTables($payload['tables'] ?? [])
            : $this->sqlExporter->listTables();

        $export = DatabaseExport::query()->create([
            'type' => DatabaseExportType::from($payload['type']),
            'scope' => $scope,
            'tables' => $scope === DatabaseExportScope::Tables ? $tables : null,
            'chunk_rows' => max(100, min(10000, (int) ($payload['chunk_rows'] ?? 1000))),
            'status' => DatabaseExportStatus::Pending,
            'total_tables' => count($tables),
            'created_by' => $admin->id,
        ]);

        ProcessDatabaseExportJob::dispatch($export->id);

        return $export;
    }

    public function process(DatabaseExport $export): void
    {
        $export->update([
            'status' => DatabaseExportStatus::Processing,
            'error_message' => null,
        ]);

        try {
            $tables = $export->scope === DatabaseExportScope::Tables
                ? ($export->tables ?? [])
                : $this->sqlExporter->listTables();

            $directory = 'database-exports/'.$export->id;
            Storage::disk('local')->makeDirectory($directory);

            $partNumber = 1;
            $totalRows = 0;
            $processedRows = 0;
            $partFiles = [];

            foreach ($tables as $table) {
                $totalRows += $this->sqlExporter->countRows($table);
            }

            $export->update(['total_rows' => $totalRows]);

            $header = $this->buildHeader($export);
            $headerFile = "{$directory}/part-".str_pad((string) $partNumber, 4, '0', STR_PAD_LEFT).'-header.sql';
            Storage::disk('local')->put($headerFile, $header);
            $partFiles[] = $headerFile;
            $partNumber++;

            foreach ($tables as $index => $table) {
                $structureFile = "{$directory}/part-".str_pad((string) $partNumber, 4, '0', STR_PAD_LEFT)."-{$table}-structure.sql";
                Storage::disk('local')->put($structureFile, $this->sqlExporter->exportTableStructure($table));
                $partFiles[] = $structureFile;
                $partNumber++;

                $rowCount = $this->sqlExporter->countRows($table);
                $chunkRows = max(100, (int) $export->chunk_rows);
                $offset = 0;
                $chunkIndex = 1;

                while ($offset < $rowCount) {
                    $dataSql = $this->sqlExporter->exportTableDataChunk($table, $offset, $chunkRows);

                    if ($dataSql !== '') {
                        $dataFile = "{$directory}/part-".str_pad((string) $partNumber, 4, '0', STR_PAD_LEFT)."-{$table}-data-".str_pad((string) $chunkIndex, 4, '0', STR_PAD_LEFT).'.sql';
                        Storage::disk('local')->put($dataFile, $dataSql);
                        $partFiles[] = $dataFile;
                        $partNumber++;

                        $chunkCount = substr_count($dataSql, "INSERT INTO ");
                        $processedRows += $chunkCount;
                        $export->update(['processed_rows' => $processedRows]);
                    }

                    $offset += $chunkRows;
                    $chunkIndex++;
                }

                $export->update([
                    'processed_tables' => $index + 1,
                    'processed_rows' => $processedRows,
                ]);
            }

            $manifest = [
                'export_id' => $export->id,
                'type' => $export->type->value,
                'scope' => $export->scope->value,
                'tables' => $tables,
                'chunk_rows' => $export->chunk_rows,
                'parts' => array_map(fn (string $path): string => basename($path), $partFiles),
                'generated_at' => now()->toIso8601String(),
            ];

            Storage::disk('local')->put("{$directory}/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));

            $downloadName = $this->buildDownloadName($export);
            $zipPath = "{$directory}/{$downloadName}";
            $this->createZipArchive($directory, $zipPath, $partFiles, $manifest);

            $absoluteZipPath = Storage::disk('local')->path($zipPath);

            $export->update([
                'status' => DatabaseExportStatus::Completed,
                'total_parts' => count($partFiles),
                'storage_path' => $zipPath,
                'download_name' => $downloadName,
                'file_size' => File::exists($absoluteZipPath) ? File::size($absoluteZipPath) : null,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $export->update([
                'status' => DatabaseExportStatus::Failed,
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function latestCompletedBackup(): ?DatabaseExport
    {
        return DatabaseExport::query()
            ->where('type', DatabaseExportType::Backup)
            ->where('status', DatabaseExportStatus::Completed)
            ->latest('completed_at')
            ->first();
    }

    /**
     * @param  list<string>  $requestedTables
     * @return list<string>
     */
    private function resolveTables(array $requestedTables): array
    {
        $allowed = $this->sqlExporter->listTables();

        $tables = collect($requestedTables)
            ->filter(fn ($table): bool => is_string($table) && $table !== '')
            ->unique()
            ->values()
            ->all();

        foreach ($tables as $table) {
            if (! in_array($table, $allowed, true)) {
                throw new \InvalidArgumentException("Table [{$table}] is not allowed for export.");
            }
        }

        if ($tables === []) {
            throw new \InvalidArgumentException('At least one table must be selected for a table-scoped export.');
        }

        return $tables;
    }

    private function buildHeader(DatabaseExport $export): string
    {
        $appName = config('app.name', 'Application');

        return implode("\n", [
            '-- SQL export generated by '.$appName,
            '-- Export ID: '.$export->id,
            '-- Type: '.$export->type->value,
            '-- Scope: '.$export->scope->value,
            '-- Chunk rows: '.$export->chunk_rows,
            '-- Generated at: '.now()->toDateTimeString(),
            '',
            'SET FOREIGN_KEY_CHECKS=0;',
            '',
        ]);
    }

    private function buildDownloadName(DatabaseExport $export): string
    {
        $prefix = $export->type === DatabaseExportType::Backup ? 'backup' : 'export';
        $timestamp = now()->format('Y-m-d_His');

        return "{$prefix}_{$timestamp}_".Str::padLeft((string) $export->id, 4, '0').'.zip';
    }

    /**
     * @param  list<string>  $partFiles
     * @param  array<string, mixed>  $manifest
     */
    private function createZipArchive(string $directory, string $zipPath, array $partFiles, array $manifest): void
    {
        $zip = new ZipArchive;
        $absoluteZipPath = Storage::disk('local')->path($zipPath);

        if ($zip->open($absoluteZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create export archive.');
        }

        foreach ($partFiles as $partFile) {
            $zip->addFile(
                Storage::disk('local')->path($partFile),
                basename($partFile),
            );
        }

        $manifestPath = Storage::disk('local')->path("{$directory}/manifest.json");
        $zip->addFile($manifestPath, 'manifest.json');
        $zip->close();
    }
}
