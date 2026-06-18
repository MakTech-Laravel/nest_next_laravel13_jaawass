<?php

namespace App\Models;

use App\Enums\AdditionalInformationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManufacturerAdditionalInformationResponse extends Model
{
    protected $fillable = [
        'request_id',
        'type',
        'message',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'type' => AdditionalInformationType::class,
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ManufacturerAdditionalInformationRequest::class, 'request_id');
    }
}
