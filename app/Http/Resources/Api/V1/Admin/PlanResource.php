<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Resources\Api\V1\FeatureResource;
use App\Models\Currency;
use App\Support\Currency\MoneyPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
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
        ['name' => $name, 'description' => $description, 'button_text' => $button_text] =
            $this->resource->localizeData($locale);

        $listingCurrency = $this->currency ?? Currency::base();
        $monthly = MoneyPresenter::priceWithDisplay($this->monthly_price, $listingCurrency);
        $yearly = MoneyPresenter::priceWithDisplay($this->yearly_price, $listingCurrency);

        return [
            'id' => $this->id,
            'name' => $name,
            'description' => $description,
            'monthly_price' => $monthly['price'],
            'monthly_price_display' => $monthly['price_display'],
            // 'monthly_conversion_available' => $monthly['conversion_available'],
            'yearly_price' => $yearly['price'],
            'yearly_price_display' => $yearly['price_display'],
            // 'yearly_conversion_available' => $yearly['conversion_available'],
            'button_text' => $button_text,
            'is_popular' => $this->is_popular,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'features' => $this->whenLoaded('planFeatures', function () {
                return $this->planFeatures->map(function ($feature) {
                    return [
                        'id' => $feature->id,
                        'features' => new FeatureResource($feature->feature),

                        'input_type' => $feature->input_type,
                        'value' => $feature->value,

                    ];
                });
            }),
        ];
    }
}
