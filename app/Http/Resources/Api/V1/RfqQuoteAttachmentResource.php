<?php

namespace App\Http\Resources\Api\V1;

use App\Models\RfqQuoteAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RfqQuoteAttachment
 */
class RfqQuoteAttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->original_name,
            'original_name' => $this->original_name,
            'file_mime' => $this->file_mime,
            'size_bytes' => $this->size_bytes,
            'url' => $this->url,
        ];
    }
}
