<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\AdditionalInformationRequestStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAllManufacturerAdditionalInformationRequest extends FormRequest
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
            'page' => ['sometimes', 'integer', 'min:1'],
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    'all',
                    ...array_column(AdditionalInformationRequestStatus::cases(), 'value'),
                ]),
            ],
            'unverified_only' => ['sometimes', 'boolean'],
            'search' => ['sometimes', 'string', 'max:120'],
        ];
    }

    public function perPage(): int
    {
        return min(max((int) $this->integer('per_page', 10), 1), 100);
    }

    public function pageNumber(): int
    {
        return max((int) $this->integer('page', 1), 1);
    }

    public function statusFilter(): ?string
    {
        $status = $this->input('status');

        return is_string($status) && $status !== '' ? $status : null;
    }

    public function unverifiedOnly(): bool
    {
        if (! $this->has('unverified_only')) {
            return false;
        }

        return $this->boolean('unverified_only');
    }

    public function searchTerm(): ?string
    {
        $search = trim((string) $this->input('search', ''));

        return $search !== '' ? $search : null;
    }
}
