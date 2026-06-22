<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\ReviewStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAdminReviewRequest extends FormRequest
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
            'search' => ['sometimes', 'string', 'max:120'],
            'status' => ['sometimes', 'string', Rule::in(array_column(ReviewStatus::cases(), 'value'))],
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
        ];
    }

    public function perPage(): int
    {
        return min(max((int) $this->integer('per_page', 15), 1), 100);
    }

    public function pageNumber(): int
    {
        return max((int) $this->integer('page', 1), 1);
    }
}
