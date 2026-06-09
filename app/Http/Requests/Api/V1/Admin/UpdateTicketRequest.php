<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\TicketDepartmentType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in(TicketStatus::values())],
            'priority' => ['sometimes', 'string', Rule::in(TicketPriority::values())],
            'department_type' => ['sometimes', 'string', Rule::in(TicketDepartmentType::values())],
            'assigned_to' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where('role', UserRole::ADMIN->value)],
        ];
    }
}
