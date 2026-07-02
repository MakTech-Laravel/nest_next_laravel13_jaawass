<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewManufacturerAdditionalInformationRequest extends FormRequest
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
            'action' => ['required', 'string', Rule::in(['accept', 'reject'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'reason' => ['required_if:action,reject', 'nullable', 'string', 'max:500'],
        ];
    }

    public function action(): string
    {
        return (string) $this->input('action');
    }

    public function notes(): ?string
    {
        $notes = trim((string) $this->input('notes', ''));

        return $notes !== '' ? $notes : null;
    }

    public function reason(): ?string
    {
        $reason = trim((string) $this->input('reason', ''));

        return $reason !== '' ? $reason : null;
    }
}
