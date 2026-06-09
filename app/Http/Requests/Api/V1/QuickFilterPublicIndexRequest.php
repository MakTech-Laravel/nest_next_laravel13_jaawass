<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\QuickFilterType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuickFilterPublicIndexRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::enum(QuickFilterType::class)],
        ];
    }

    public function filterType(): QuickFilterType
    {
        return QuickFilterType::from($this->validated('type'));
    }
}
