<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\ArticleStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:articles,slug',
            'excerpt' => 'nullable|string',
            'content' =>  $this->isDraft() ? 'nullable|string' : 'required|string',
            'tags' => 'nullable|array',
            'author' => $this->isDraft() ? 'nullable|string|max:255' : 'required|string|max:255',
            'is_featured' => 'sometimes|boolean',
            'status' => 'sometimes|string|in:' . implode(',', ArticleStatusEnum::options()),
            // 'published_at' => $this->isDraft() ? 'nullable|date' : 'required|date',
            'archived_at' => 'nullable|date',
            'article_category_id' => 'required|integer|exists:article_categories,id',
            'article_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10500'
        ];
    }

    private function isDraft(): bool
    {
        return $this->input('status') === 'draft';
    }
}
