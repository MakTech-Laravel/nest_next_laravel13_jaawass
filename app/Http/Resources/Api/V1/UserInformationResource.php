<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserInformationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $request->query('locale') ?? app()->getLocale();

        [
            'company_name' => $companyName,
            'short_description' => $shortDescription,
            'long_description' => $longDescription,
            'company_type' => $companyType,
            'company_established' => $companyEstablished,
            'company_size' => $companySize,
            'revenue' => $revenue,
            'country' => $country,
            'city' => $city,
            'street_address' => $streetAddress,
            'phone' => $phone,
            'zip_code' => $zipCode,
            'capabilities' => $capabilities,
            'certifications' => $certifications,
            'export_markets' => $exportMarkets,

            'notes' => $notes,
        ] = $this->resource->localizedData($locale);

        return [
            'company_name' => $companyName,
            'short_description' => $shortDescription,
            'long_description' => $longDescription,
            'minimum_order_value' => $this->minimum_order_value,
            'company_logo' => storage_url($this->company_logo),
            'company_logo_url' => storage_url($this->company_logo),
            'company_type' => $companyType,
            'company_established' => $companyEstablished,
            'company_size' => $companySize,
            'revenue' => $revenue,
            'country' => $country,
            'city' => $city,
            'street_address' => $streetAddress,
            'phone' => $phone,
            'zip_code' => $zipCode,
            'capabilities' => $capabilities,
            'certifications' => $certifications,
            'export_markets' => $exportMarkets,
            'language_spoken' => $this->language_spoken,
            'payments_term' => $this->payments_term,
            'bussiness_license' => storage_url($this->bussiness_license),
            'bussiness_license_url' => $this->bussiness_license_url,
            'company_website' => $this->company_website,
            'notes' => $notes,
            'factory_production' => $this->factory_production,
            'mulitple_factories' => $this->mulitple_factories,
            'industries' => $this->whenLoaded('industries'),
            'created_at' => TimezoneFormatter::format($this->created_at),

            // 'updated_at' => optional($this->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
