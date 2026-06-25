<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\SupplierReportPriority;
use App\Enums\SupplierReportStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexSupplierReportRequest extends FormRequest
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
            'status' => ['sometimes', 'string', Rule::in(SupplierReportStatus::values())],
            'priority' => ['sometimes', 'string', Rule::in(SupplierReportPriority::values())],
            'supplier_id' => ['sometimes', 'integer', 'exists:users,id'],
            'reporter_id' => ['sometimes', 'integer', 'exists:users,id'],
            'search' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
