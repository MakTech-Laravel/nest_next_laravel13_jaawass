<?php

namespace App\Http\Resources\Api\V1\Product;

use App\Http\Resources\Api\V1\Admin\SubCategoryResource;
use App\Http\Resources\Api\V1\IndustryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $locale = $request->query('locale') ?? app()->getLocale();

        ['name' => $name, 'description' => $description] = $this->resource->localizedData($locale);

        return [
            'id' => $this->id,
            'name' => $name,
            'slug' => $this->slug,
            'description' => $description,
            'status' => $this->status,
            'is_approved' => (bool) $this->is_approved,
            'view_count' => $this->view_count,
            'inquiry_count' => $this->inquiry_count,
            'image' => storage_url($this->image),

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

        ];
    }
}
