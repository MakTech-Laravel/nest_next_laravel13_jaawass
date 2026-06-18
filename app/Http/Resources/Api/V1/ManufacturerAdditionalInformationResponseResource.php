<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\AdditionalInformationType;
use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManufacturerAdditionalInformationResponseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'message' => $this->message,
            'file_path' => $this->file_path,
            'file_url' => $this->file_path ? storage_url($this->file_path) : null,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'created_at' => TimezoneFormatter::format($this->created_at),
        ];
    }
}
