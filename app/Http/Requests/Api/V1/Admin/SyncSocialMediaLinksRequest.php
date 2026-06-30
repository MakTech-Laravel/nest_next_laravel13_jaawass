<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

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
            'links' => ['required', 'array'],
            'links.*.id' => ['nullable', 'integer', 'exists:social_media_links,id'],
            'links.*.platform' => ['required', 'string', 'max:255'],
            'links.*.icon' => ['required', 'string', 'max:50'],
            'links.*.url' => ['required', 'url', 'max:2048'],
            'links.*.enabled' => ['required', 'boolean'],
            'links.*.sort' => ['required', 'integer', 'min:1'],
        ];
    }
}
