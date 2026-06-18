<?php

namespace App\Models;

use App\Enums\AdditionalInformationRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManufacturerAdditionalInformationRequest extends Model
{
    protected $fillable = [
        'user_id',
        'requested_by',
        'token',
        'message',
        'allowed_types',
        'status',
        'expires_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'allowed_types' => 'array',
            'status' => AdditionalInformationRequestStatus::class,
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ManufacturerAdditionalInformationResponse::class, 'request_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isSubmittable(): bool
    {
        return $this->status === AdditionalInformationRequestStatus::Pending
            && ! $this->isExpired();
    }
}
