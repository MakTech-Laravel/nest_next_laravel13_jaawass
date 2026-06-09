<?php

namespace App\Http\Resources\Api\V1\Product;

use App\Models\Currency;
use App\Support\Currency\MoneyPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingPackagingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
         // Prefer explicit ?locale=, otherwise use middleware-resolved locale.
        $locale = $request->query('locale') ?? app()->getLocale();

        // Uses your existing LocaleTranslationResolver under the hood
        [
            'packaging_type' => $packagingType, 
            'port_of_loading' => $portOfLoading, 
            'packaging_dimensions' => $packagingDimensions, 
            'packaging_weight' => $packagingWeight,
            'packaging_description' => $packagingDescription
        ] = $this->localizedData($locale);
        
        $listingCurrency = $this->currency ?? Currency::base();

        $packaging_cost_per_unit = MoneyPresenter::priceWithDisplay($this->packaging_cost_per_unit, $listingCurrency);


        return [
            'id' => $this->id,
            'packaging_type' => $packagingType,
            'port_of_loading' => $portOfLoading,
            'packaging_dimensions' => $packagingDimensions,
            'packaging_weight' => $packagingWeight,
            'packaging_description' => $packagingDescription,
            'packaging_cost_per_unit' => $packaging_cost_per_unit,
        ];
    }
}
