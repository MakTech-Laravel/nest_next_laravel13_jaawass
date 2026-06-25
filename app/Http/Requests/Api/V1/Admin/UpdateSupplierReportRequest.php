<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\SupplierReportPriority;
use App\Enums\SupplierReportStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierReportRequest extends FormRequest
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
            'assigned_to' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where('role', UserRole::ADMIN->value)],
            'message' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
