<?php

namespace App\Models;

use App\Enums\SupplierReportPriority;
use App\Enums\SupplierReportReason;
use App\Enums\SupplierReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierReport extends Model
{
    protected $fillable = [
        'reporter_id',
        'supplier_id',
        'reason',
        'details',
        'status',
        'priority',
        'assigned_to',
        'resolved_by',
        'resolved_at',
        'source_page',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'reason' => SupplierReportReason::class,
            'status' => SupplierReportStatus::class,
            'priority' => SupplierReportPriority::class,
            'resolved_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(SupplierReportStatusLog::class);
    }

    public function isClosed(): bool
    {
        $status = $this->status instanceof SupplierReportStatus
            ? $this->status
            : SupplierReportStatus::tryFrom((string) $this->status);

        return $status?->isClosed() ?? false;
    }
}
