<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreTicketMessageRequest extends FormRequest
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
            'message' => ['nullable', 'string', 'max:20000'],
            'attachments' => ['sometimes', 'array', 'max:'.config('tickets.attachments.max_per_message', 5)],
            'attachments.*' => [
                'file',
                File::types(config('tickets.attachments.allowed_extensions', []))
                    ->max(config('tickets.attachments.max_file_kb', 10240)),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $message = trim((string) $this->input('message', ''));
            $attachments = $this->file('attachments', []);

            if ($message === '' && count($attachments) === 0) {
                $v->errors()->add('message', __('api.ticket_message_or_attachment_required'));
            }
        });
    }
}
