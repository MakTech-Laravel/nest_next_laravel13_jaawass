<?php

namespace App\Http\Requests\Api\V1\Admin\QuickFilter;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SortQuickFilterOptionRequest extends FormRequest
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
            'direction' => ['required', 'string', Rule::in(['up', 'down'])],
        ];
    }
}
