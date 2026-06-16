<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class OrderBuyerSelectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fullName = trim("{$this->first_name} {$this->last_name}");
        $companyName = $this->company?->company_name;
        $label = $companyName !== null && $companyName !== ''
            ? "{$companyName} - {$fullName}"
            : $fullName;

        return [
            'value' => $this->id,
            'label' => $label,
            'email' => $this->email,
            'company_name' => $companyName,
            'location' => $this->company?->country,
        ];
    }
}
