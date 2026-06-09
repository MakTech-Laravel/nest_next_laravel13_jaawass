<?php

namespace App\Http\Resources\Api\V1\Manufacturer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'certificate_type_id' => $this->certificate_type_id,
            'user_id' => $this->user_id,
            'issuing_body' => $this->issuing_body,
            'certificate_number' => $this->certificate_number,
            'issue_date' => $this->issue_date,
            'expiry_date' => $this->expiry_date,
            'certificate_pdf' => $this->certificate_pdf,
            'certificate_pdf_url' => $this->certificate_pdf_url,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
