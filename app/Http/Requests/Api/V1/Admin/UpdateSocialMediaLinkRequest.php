<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Models\SocialMediaLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSocialMediaLinkRequest extends FormRequest
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
            'platform' => ['sometimes', 'string', 'max:255'],
            'icon' => ['sometimes', 'string', 'max:50'],
            'url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'enabled' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->has('enabled') && ! $this->has('url')) {
                return;
            }

            $linkId = $this->route('socialMediaLink');
            $existing = is_numeric($linkId)
                ? SocialMediaLink::query()->find((int) $linkId)
                : null;

            $enabled = $this->has('enabled')
                ? filter_var($this->input('enabled'), FILTER_VALIDATE_BOOLEAN)
                : (bool) ($existing?->enabled);

            $url = $this->has('url')
                ? trim((string) $this->input('url'))
                : trim((string) ($existing?->url ?? ''));

            if ($enabled && ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false)) {
                $validator->errors()->add('url', __('validation.url', ['attribute' => 'url']));
            }
        });
    }
}
