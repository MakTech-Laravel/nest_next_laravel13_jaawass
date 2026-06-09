<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUserRequest extends FormRequest
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'order_by' => ['sometimes', 'string', Rule::in(['created_at', 'first_name', 'last_name', 'email', 'role'])],
            'order_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'role' => ['sometimes', 'nullable', Rule::enum(UserRole::class)],
            'status' => ['sometimes', 'nullable', Rule::enum(UserStatus::class)],
        ];
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 10);
    }

    public function pageNumber(): int
    {
        return $this->integer('page', 1);
    }

    public function searchTerm(): ?string
    {
        $value = $this->input('search');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function orderByColumn(): string
    {
        return $this->input('order_by', 'created_at');
    }

    public function orderDirection(): string
    {
        return $this->input('order_direction', 'desc');
    }

    public function filterRole(): ?string
    {
        return $this->input('role');
    }

    public function filterStatus(): ?string
    {
        return $this->input('status');
    }
}
