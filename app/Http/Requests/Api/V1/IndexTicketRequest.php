<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketDepartmentType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'max:120'],
            'status' => ['sometimes', 'string', Rule::in(TicketStatus::values())],
            'priority' => ['sometimes', 'string', Rule::in(TicketPriority::values())],
            'department_type' => ['sometimes', 'string', Rule::in(TicketDepartmentType::values())],
        ];
    }
}
