<?php

namespace App\Http\Requests\Api\V1\Admin\QuickFilter;

use App\Enums\QuickFilterType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuickFilterOptionRequest extends FormRequest
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
            'display_label' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'string', 'max:191'],
            'is_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function filterType(): QuickFilterType
    {
        return QuickFilterType::from($this->validated('type'));
    }
}
