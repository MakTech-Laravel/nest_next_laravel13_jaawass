<?php

namespace App\Models;

use App\Enums\DatabaseExportScope;
use App\Enums\DatabaseExportStatus;
use App\Enums\DatabaseExportType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseExport extends Model
{
    protected $fillable = [
        'type',
        'scope',
        'tables',
        'chunk_rows',
        'status',
        'total_tables',
        'processed_tables',
        'total_rows',
        'processed_rows',
        'total_parts',
        'storage_path',
        'download_name',
        'file_size',
        'error_message',
        'created_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'tables' => 'array',
            'type' => DatabaseExportType::class,
            'scope' => DatabaseExportScope::class,
            'status' => DatabaseExportStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function progressPercent(): int
    {
        if ($this->total_rows > 0) {
            return (int) min(100, round(($this->processed_rows / $this->total_rows) * 100));
        }

        if ($this->total_tables > 0) {
            return (int) min(100, round(($this->processed_tables / $this->total_tables) * 100));
        }

        return $this->status === DatabaseExportStatus::Completed ? 100 : 0;
    }
}
