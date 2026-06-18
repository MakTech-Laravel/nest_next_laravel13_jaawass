<?php

namespace App\Http\Requests\Api\V1\Manufacturer\Profile;

use App\Services\Subscription\PlanEntitlementResolver;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateManufacturerProfileRequest extends FormRequest
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
            // Company fields
            'company_name' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'long_description' => 'nullable|string|max:2000',
            'company_established' => 'nullable|string|max:4',
            'company_size' => 'nullable|string|max:50',
           
            'country' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'street_address' => 'required|string|max:255',

            'company_logo' => 'nullable|file|mimes:jpeg,png,jpg|max:2024',
           
            'minimum_order_value' => 'nullable|integer|min:0',
          
            'company_type' => 'nullable|string|max:100',
            
           
            'revenue' => 'nullable|string|max:50',
            
            'capabilities' => 'nullable|array',

            'capabilities.*' => 'string|max:255',

            'certifications' => 'nullable|array',

            'certifications.*' => 'string|max:255',

            'export_markets' => 'nullable|array',

            'export_markets.*' => 'string|max:255',

            'language_spoken' => 'nullable|array',

            'language_spoken.*' => 'string|max:100',

            'payments_term' => 'nullable|array',

            'payments_term.*' => 'string|max:255',

            'factory_production' => 'nullable|boolean',

            'mulitple_factories' => 'nullable|boolean',

            'industries_id' => 'nullable|array',

            'industries_id.*' => 'integer|exists:industries,id',


            // Factory Information 

            // 'factory_size' => 'nullable|integer|min:0',
             
            'production_lines' => 'nullable|integer|min:0',

            'factory_images' => 'nullable|array',

            'factory_images.*' => 'file|mimes:jpeg,png,jpg|max:5060',

            'remove_images' => 'nullable|array',

            'remove_images.*' => 'integer',
        ];
    }

    protected function passedValidation(): void
    {
        $exportMarkets = $this->input('export_markets');

        if (! is_array($exportMarkets) || $exportMarkets === []) {
            return;
        }

        app(PlanEntitlementResolver::class)
            ->for($this->user())
            ->assertFeature('export_markets_section');
    }
}
