<?php

namespace App\Http\Requests\Api\V1\Buyer;

use App\Enums\SupplierReportReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', Rule::in(SupplierReportReason::values())],
            'details' => ['nullable', 'string', 'max:5000'],
            'source_page' => ['nullable', 'string', 'max:500'],
        ];
    }
}
