<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Enums\DatabaseExportStatus;
use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DatabaseExportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof DatabaseExportStatus
            ? $this->status
            : DatabaseExportStatus::tryFrom((string) $this->status);

        return [
            'id' => $this->id,
            'type' => $this->type?->value ?? $this->type,
            'scope' => $this->scope?->value ?? $this->scope,
            'tables' => $this->tables,
            'chunk_rows' => $this->chunk_rows,
            'status' => $status?->value ?? $this->status,
            'total_tables' => $this->total_tables,
            'processed_tables' => $this->processed_tables,
            'total_rows' => $this->total_rows,
            'processed_rows' => $this->processed_rows,
            'total_parts' => $this->total_parts,
            'progress_percent' => $this->progressPercent(),
            'download_name' => $this->download_name,
            'file_size' => $this->file_size,
            'error_message' => $this->error_message,
            'completed_at' => TimezoneFormatter::format($this->completed_at),
            'created_at' => TimezoneFormatter::format($this->created_at),
            'updated_at' => TimezoneFormatter::format($this->updated_at),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator === null ? null : [
                'id' => $this->creator->id,
                'name' => trim($this->creator->first_name.' '.$this->creator->last_name) ?: null,
                'email' => $this->creator->email,
            ]),
            'download_url' => $status === DatabaseExportStatus::Completed
                ? url("/api/v1/admin/database/exports/{$this->id}/download")
                : null,
        ];
    }
}
