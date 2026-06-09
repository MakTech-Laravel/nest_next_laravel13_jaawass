<?php

namespace App\Models;

use App\Enums\CertificateStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = [
        'certificate_type_id',
        'user_id',
        'issuing_body',
        'certificate_number',
        'issue_date',
        'expiry_date',
        'certificate_pdf',
        'notes',
        'status',
    ];

    public function certificateType(): BelongsTo
    {
        return $this->belongsTo(CertificateType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCertificatePdfUrlAttribute(): string
    {
        return storage_url($this->certificate_pdf);
    }

        public function scopeValid(Builder $query): Builder
    {
        return $query->where('status', CertificateStatus::VALID->value);
    }
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', CertificateStatus::EXPIRED->value);
    }
    public function scopeExpiringSoon(Builder $query): Builder
    {
        return $query->where('status', CertificateStatus::EXPIRING_SOON->value);
    }
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', CertificateStatus::PENDING->value);
    }

    protected  $appends = [
        'certificate_pdf_url'
    ];
}
