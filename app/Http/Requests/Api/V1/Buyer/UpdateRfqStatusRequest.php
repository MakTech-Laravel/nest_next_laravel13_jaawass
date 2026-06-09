<?php

namespace App\Http\Requests\Api\V1\Buyer;

use App\Enums\RfqSubmissionStatus;
use App\Models\RfqSubmission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRfqStatusRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $status = strtolower((string) $this->input('status', ''));

        $normalizedStatus = match ($status) {
            'reviewing' => RfqSubmissionStatus::InReview->value,
            default => $status,
        };

        $this->merge([
            'status' => $normalizedStatus,
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(RfqSubmission::statuses())],
        ];
    }
}
