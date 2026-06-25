<?php

namespace App\Models;

use App\Enums\SupplierReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierReportStatusLog extends Model
{
    protected $fillable = [
        'supplier_report_id',
        'admin_id',
        'from_status',
        'to_status',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => SupplierReportStatus::class,
            'to_status' => SupplierReportStatus::class,
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(SupplierReport::class, 'supplier_report_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
