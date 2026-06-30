<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAboutPageRequest extends FormRequest
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
            'locale' => ['sometimes', 'string', 'max:10'],
            'enabled' => ['sometimes', 'boolean'],
            'content' => ['required', 'array'],
            'content.hero' => ['required', 'array'],
            'content.hero.title' => ['required', 'string', 'max:500'],
            'content.hero.subtitle' => ['required', 'string', 'max:2000'],
            'content.story' => ['required', 'array'],
            'content.story.title' => ['required', 'string', 'max:500'],
            'content.story.paragraphs' => ['required', 'array', 'min:1'],
            'content.story.paragraphs.*' => ['nullable', 'string', 'max:5000'],
            'content.mission' => ['required', 'array'],
            'content.mission.title' => ['required', 'string', 'max:500'],
            'content.mission.description' => ['required', 'string', 'max:5000'],
            'content.vision' => ['required', 'array'],
            'content.vision.title' => ['required', 'string', 'max:500'],
            'content.vision.description' => ['required', 'string', 'max:5000'],
            'content.values' => ['required', 'array'],
            'content.values.title' => ['required', 'string', 'max:500'],
            'content.values.subtitle' => ['required', 'string', 'max:2000'],
            'content.values.items' => ['required', 'array', 'min:1'],
            'content.values.items.*.id' => ['required', 'string', 'max:100'],
            'content.values.items.*.icon' => ['required', 'string', 'max:100'],
            'content.values.items.*.title' => ['required', 'string', 'max:500'],
            'content.values.items.*.description' => ['required', 'string', 'max:2000'],
            'content.values.items.*.enabled' => ['required', 'boolean'],
            'content.why_different' => ['required', 'array'],
            'content.why_different.title' => ['required', 'string', 'max:500'],
            'content.why_different.points' => ['required', 'array', 'min:1'],
            'content.why_different.points.*.id' => ['required', 'string', 'max:100'],
            'content.why_different.points.*.title' => ['required', 'string', 'max:500'],
            'content.why_different.points.*.description' => ['required', 'string', 'max:5000'],
            'content.why_different.points.*.enabled' => ['required', 'boolean'],
            'content.cta' => ['required', 'array'],
            'content.cta.title' => ['required', 'string', 'max:500'],
            'content.cta.subtitle' => ['required', 'string', 'max:2000'],
            'content.cta.buyer_button_text' => ['required', 'string', 'max:200'],
            'content.cta.manufacturer_button_text' => ['required', 'string', 'max:200'],
        ];
    }
}
