<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Admin\SubCategoryResource;
use App\Http\Resources\Api\V1\Product\PricingQuantityResource;
use App\Http\Resources\Api\V1\Product\ProductAvailableOptionResource;
use App\Http\Resources\Api\V1\Product\ProductCustomizationOptionResource;
use App\Http\Resources\Api\V1\Product\ProductKeyFeatureResource;
use App\Http\Resources\Api\V1\Product\ProductShippingMethodResource;
use App\Http\Resources\Api\V1\Product\ProductSpecificationResource;
use App\Http\Resources\Api\V1\Product\ShippingPackagingResource;
use App\Models\Currency;
use App\Models\Product;
use App\Support\Currency\MoneyPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * ProductResource
 *
 * Resolves translated name/description automatically using
 * Product::localizedNameAndDescription() which delegates to
 * LocaleTranslationResolver (your existing service).
 *
 * The locale is resolved (in order of priority):
 *   1. ?locale= query param  (e.g. GET /products?locale=ar)
 *   2. Accept-Language header
 *   3. app()->getLocale()    (default from config/app.php)
 *
 * Assumes 'translations' is always eager-loaded by the controller.
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Prefer explicit ?locale=, otherwise use middleware-resolved locale.
        $locale = $request->query('locale') ?? app()->getLocale();

        // Uses your existing LocaleTranslationResolver under the hood
        ['name' => $name, 'description' => $description] =
            $this->resource->localizedData($locale);

        $listingCurrency = $this->currency ?? Currency::base();
        $money = MoneyPresenter::priceWithDisplay($this->price, $listingCurrency);

        return [
            'id' => $this->id,
            'name' => $name,
            'slug' => $this->slug,
            'description' => $description,
            'price' => $money['price'],
            'price_display' => $money['price_display'],
            'conversion_available' => $money['conversion_available'],
            'quantity' => $this->quantity,
            'inquiry_count' => $this->inquiry_count,
            'view_count' => $this->view_count,
            'is_approved' => (bool) $this->is_approved,
            'image' => storage_url($this->image),
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Optional: expose available locales for the frontend
            'available_locales' => $this->whenLoaded(
                'translations',
                fn () => $this->translations->pluck('locale')->sort()->values()
            ),

            // Same relation serialization as manufacturer `Product\ProductResource`
            'category' => $this->whenLoaded('category', function () {
                return new IndustryResource($this->category);
            }),

            'sub_category' => $this->whenLoaded('subCategory', function () {
                return new SubCategoryResource($this->subCategory);
            }),

            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => storage_url($image->image_path),
                    ];
                });
            }),

            'pricing_quantities' => $this->whenLoaded('pricingQuantities', function () {
                return new PricingQuantityResource($this->pricingQuantities);
            }),

            'specifications' => $this->whenLoaded('specifications', function () {
                return ProductSpecificationResource::collection($this->specifications);
            }),

            'product_key_features' => $this->whenLoaded('productKeyFeatures', function () {
                return ProductKeyFeatureResource::collection($this->productKeyFeatures);
            }),

            'customization_options' => $this->whenLoaded('customizationOptions', function () {
                return ProductCustomizationOptionResource::collection($this->customizationOptions);
            }),

            'shipping_packaging' => $this->whenLoaded('shippingPackaging', function () {
                return new ShippingPackagingResource($this->shippingPackaging);
            }),

            'available_options' => $this->whenLoaded('availableOptions', function () {
                return new ProductAvailableOptionResource($this->availableOptions);
            }),

            'shipping_methods' => $this->whenLoaded('shippingMethods', function () {
                return ProductShippingMethodResource::collection($this->shippingMethods);
            }),
            'review_stats' => $this->whenLoaded('reviews', fn () => $this->reviewStats()),
            'reviews' => ProductReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewStats(): array
    {
        /** @var Collection<int, mixed> $reviews */
        $reviews = $this->reviews;
        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0
            ? round((float) $reviews->avg('rating'), 1)
            : 0.0;

        $breakdown = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $count = $reviews->where('rating', $star)->count();
            $breakdown[] = [
                'rating' => $star,
                'count' => $count,
                'percentage' => $totalReviews > 0 ? (int) round(($count / $totalReviews) * 100) : 0,
            ];
        }

        return [
            'average_rating' => $averageRating,
            'total_reviews' => $totalReviews,
            'breakdown' => $breakdown,
        ];
    }
}
