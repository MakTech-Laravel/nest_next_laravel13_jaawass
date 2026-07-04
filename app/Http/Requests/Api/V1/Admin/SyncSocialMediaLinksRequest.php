<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SyncSocialMediaLinksRequest extends FormRequest
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
            'links' => ['required', 'array', 'min:1'],
            'links.*.id' => ['required', 'integer', 'exists:social_media_links,id'],
            'links.*.platform' => ['required', 'string', 'max:255'],
            'links.*.icon' => ['required', 'string', 'max:50'],
            'links.*.url' => ['nullable', 'string', 'max:2048'],
            'links.*.enabled' => ['required', 'boolean'],
            'links.*.sort' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $links = $this->input('links', []);

            if (! is_array($links)) {
                return;
            }

            foreach ($links as $index => $link) {
                if (! is_array($link)) {
                    continue;
                }

                $enabled = filter_var($link['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $url = is_string($link['url'] ?? null) ? trim($link['url']) : '';

                if ($enabled && ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false)) {
                    $validator->errors()->add(
                        "links.{$index}.url",
                        __('validation.url', ['attribute' => 'url'])
                    );
                }
            }
        });
    }
}
