<?php

namespace App\Http\Requests\Api\V1\Concerns;

use App\Enums\OrderStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

trait ValidatesOrderStatusUpdate
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function orderStatusUpdateRules(): array
    {
        return [
            'status' => ['required', new Enum(OrderStatus::class)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'locale' => ['sometimes', 'nullable', 'string', 'max:10'],
            'photos' => ['sometimes', 'array', 'max:5'],
            'photos.*' => [
                'file',
                File::types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(51200),
            ],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                File::types(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png', 'webp'])
                    ->max(51200),
            ],
        ];
    }

    protected function validateOrderStatusUpdateContent(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $notes = trim((string) $this->input('notes', ''));
            $photos = $this->file('photos', []);
            $attachments = $this->file('attachments', []);

            if ($notes === '' && $photos === [] && $attachments === []) {
                $validator->errors()->add(
                    'notes',
                    __('api.order_status_update_content_required'),
                );
            }
        });
    }
}
