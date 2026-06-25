<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\DatabaseExportScope;
use App\Enums\DatabaseExportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDatabaseExportRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in(DatabaseExportType::values())],
            'scope' => ['required', 'string', Rule::in(DatabaseExportScope::values())],
            'tables' => ['required_if:scope,tables', 'array', 'min:1'],
            'tables.*' => ['string', 'max:128'],
            'chunk_rows' => ['sometimes', 'integer', 'min:100', 'max:10000'],
        ];
    }
}
