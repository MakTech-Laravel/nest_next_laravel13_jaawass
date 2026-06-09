<?php

namespace App\Http\Resources\Api\V1\Product;

use App\Http\Resources\Api\V1\CurrencyResource;
use App\Models\Currency;
use App\Support\Currency\MoneyPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricingQuantityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $listingCurrency = $this->currency ?? Currency::base();

        $min_price = MoneyPresenter::priceWithDisplay($this->min_price, $listingCurrency);
        $max_price = MoneyPresenter::priceWithDisplay($this->max_price, $listingCurrency);
        return [
            'id' => $this->id,
            'min_price' => $min_price,
            'max_price' => $max_price,
            'currency' => $this->whenLoaded('currency', function(){
                return new CurrencyResource($this->currency);
            }),
            'minimum_order_quantity' => $this->minimum_order_quantity,
            'unit' => $this->unit,
            'lead_time' => $this->lead_time,
            'production_capacity' => $this->production_capacity,
            'production_duration' => $this->production_duration,
            'production_unit' => $this->production_unit,

        ];
    }
}
