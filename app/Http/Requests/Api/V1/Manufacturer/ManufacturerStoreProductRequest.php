<?php

namespace App\Http\Requests\Api\V1\Manufacturer;

use App\Enums\Api\V1\ProductStatusEnum;
use App\Services\Subscription\PlanEntitlementResolver;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ManufacturerStoreProductRequest extends FormRequest
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
        $isDraft = $this->input('status') === ProductStatusEnum::DRAFT->value;

        return [

            // Basic Info
            'name' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'description' => $isDraft ? 'nullable|string' : 'required|string',
            'category_id' => $isDraft ? 'nullable|integer|exists:industries,id' : 'required|integer|exists:industries,id',
            'sub_category_id' => $isDraft ? 'nullable|integer|exists:sub_categories,id' : 'required|integer|exists:sub_categories,id',

            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:100',

            'status' => 'required|in:active,inactive,draft',
            'locale' => 'nullable|string|max:10',

            // Images
            'product_images' => 'nullable|array',
            'product_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:100000',

            // Pricing
            'min_price' => $isDraft ? 'nullable|numeric|min:1' : 'required|numeric|min:1',
            'max_price' => $isDraft ? 'nullable|numeric|gte:min_price' : 'required|numeric|gte:min_price',
            'currency_id' => $isDraft ? 'nullable|integer|exists:currencies,id' : 'required|integer|exists:currencies,id',

            'minimum_order_quantity' => $isDraft ? 'nullable|integer|min:1' : 'required|integer|min:1',
            'unit' => $isDraft ? 'nullable|string|max:50' : 'required|string|max:50',

            'lead_time' => 'nullable|string|max:100',

            'production_capacity' => 'nullable|integer|min:1',
            'production_duration' => 'nullable|string|max:50',
            'production_unit' => 'nullable|string|max:50',

            // Specifications
            'product_specifications' => 'nullable|array',
            'product_specifications.*.specification_title' => 'required_with:product_specifications|string|max:255',
            'product_specifications.*.specification_value' => 'required_with:product_specifications|string|max:500',

            // Features & Customize
            'key_features' => 'nullable|array',
            'key_features.*' => 'string|max:255',

            'customize_options' => 'nullable|array',
            'customize_options.*' => 'string|max:255',

            // Packaging
            'packaging_type' => 'nullable|string|max:255',
            'port_of_loading' => 'nullable|string|max:255',
            'packaging_dimensions' => 'nullable|string|max:100',
            'packaging_weight' => 'nullable|string|max:50',
            'packaging_cost_per_unit' => 'nullable|numeric|min:0',
            'packaging_description' => 'nullable|string',

            // Shipping Methods
            'shipping_methods' => 'nullable|array',
            'shipping_methods.*' => 'integer|exists:shipping_methods,id',

            // Additional Info
            'sample_available' => 'nullable|boolean',
            'sample_price' => 'nullable|numeric|min:0|required_if:sample_available,true',

            'customization_available' => 'nullable|boolean',
            'customization_detail' => 'nullable|string|required_if:customization_available,true',

            'product_broschure' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ];
    }

    protected function passedValidation(): void
    {
        app(PlanEntitlementResolver::class)
            ->for($this->user())
            ->assertWithinLimit('product_limit', $this->user()->products()->count());
    }
}
