<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TicketAttachment
 */
class TicketAttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'file_mime' => $this->file_mime,
            'size_bytes' => $this->size_bytes,
            'disk' => $this->disk,
            'file_path' => $this->file_path,
            'url' => $this->url,
        ];
    }
}
