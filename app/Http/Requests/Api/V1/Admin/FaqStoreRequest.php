<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FaqStoreRequest extends FormRequest
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
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'faq_category_id' => 'required|exists:faq_categories,id',
            'sort' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'question.required' => 'The question field is required.',
            'answer.required' => 'The answer field is required.',
            'faq_category_id.required' => 'The FAQ category field is required.',
            'faq_category_id.exists' => 'The selected FAQ category does not exist.',
            'sort.integer' => 'The sort field must be an integer.',
        ];
    }
}
