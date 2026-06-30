<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLegalPageContentRequest extends FormRequest
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
            'locale' => ['sometimes', 'string', 'max:10'],
            'title' => ['required', 'string', 'max:255'],
            'last_updated' => ['sometimes', 'nullable', 'string', 'max:255'],
            'enabled' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'integer', 'min:0'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.id' => ['sometimes', 'nullable', 'integer', 'exists:legal_page_sections,id'],
            'sections.*.section_key' => ['required', 'string', 'max:128'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.content' => ['required', 'string'],
            'sections.*.sort' => ['required', 'integer', 'min:0'],
        ];
    }
}
