<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\AdditionalInformationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SubmitManufacturerAdditionalInformationRequest extends FormRequest
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
            'responses' => ['required', 'array', 'min:1'],
            'responses.*.type' => ['required', 'string', Rule::in(AdditionalInformationType::values())],
            'responses.*.message' => ['nullable', 'string', 'max:5000'],
            'responses.*.file' => ['nullable', 'file'],
            'responses.*.video' => ['nullable', 'file'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $responses = $this->input('responses', []);

            if (! is_array($responses)) {
                return;
            }

            foreach ($responses as $index => $response) {
                if (! is_array($response)) {
                    continue;
                }

                $type = (string) ($response['type'] ?? '');

                if ($type !== AdditionalInformationType::Video->value) {
                    continue;
                }

                $file = $this->resolveResponseFile((int) $index, $response);

                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $maxKb = (int) config('manufacturer_additional_information.max_file_sizes_kb.video', 51200);

                if ($file->getSize() > ($maxKb * 1024)) {
                    $validator->errors()->add(
                        "responses.{$index}.file",
                        __('manufacturer_additional_information.file_too_large'),
                    );
                }

                if (! $this->isAllowedVideoUpload($file)) {
                    $validator->errors()->add(
                        "responses.{$index}.file",
                        __('manufacturer_additional_information.invalid_file_type'),
                    );
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if ($key !== null || ! is_array($validated['responses'] ?? null)) {
            return $validated;
        }

        foreach ($validated['responses'] as $index => &$response) {
            if (($response['type'] ?? '') !== AdditionalInformationType::Video->value) {
                continue;
            }

            if (! isset($response['file']) || ! $response['file'] instanceof UploadedFile) {
                $videoFile = $this->file("responses.{$index}.video");

                if ($videoFile instanceof UploadedFile) {
                    $response['video'] = $videoFile;
                }
            }
        }
        unset($response);

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function resolveResponseFile(int $index, array $response): ?UploadedFile
    {
        $file = $this->file("responses.{$index}.file");

        if ($file instanceof UploadedFile) {
            return $file;
        }

        $video = $this->file("responses.{$index}.video");

        if ($video instanceof UploadedFile) {
            return $video;
        }

        $nestedFile = $response['file'] ?? null;

        return $nestedFile instanceof UploadedFile ? $nestedFile : null;
    }

    private function isAllowedVideoUpload(UploadedFile $file): bool
    {
        $allowedMimes = config('manufacturer_additional_information.allowed_mimes.video', []);
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());

        return collect($allowedMimes)->contains(
            fn (string $allowed): bool => str_contains($mime, $allowed) || $extension === $allowed,
        );
    }
}
