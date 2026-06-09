<?php

namespace App\Http\Controllers\Api\V1\Manufacturer;

use App\Enums\Api\V1\ProductStatusEnum;
use App\Filters\Api\V1\Manufacturer\ProductFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Manufacturer\ManufacturerIndexRequest;
use App\Http\Requests\Api\V1\Manufacturer\ManufacturerStoreProductRequest;
use App\Http\Requests\Api\V1\Manufacturer\ManufacturerUpdateProductRequest;
use App\Http\Resources\Api\V1\Product\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Manufacturer\ManufacturerProductStatsService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ManufacturerProductController extends Controller
{
    /**
     * Dashboard aggregates for the authenticated manufacturer's catalog.
     *
     * `total_inquiries` counts conversations where another user created the thread
     * and the manufacturer participates (typical buyer-initiated contact).
     */
    public function stats(Request $request, ManufacturerProductStatsService $productStats)
    {
        $data = $productStats->getStats($request->user());

        return sendResponse(
            true,
            __('common.success'),
            $data,
            HttpStatus::HTTP_OK
        );
    }

    public function index(ManufacturerIndexRequest $request)
    {
        $Product = ProductFilter::apply(Product::query()->with([
            'user',
            'currency',
            'category',
            'subCategory',
            'images',
            'pricingQuantities.currency',
            'specifications',
            'productKeyFeatures',
            'customizationOptions',
            'shippingPackaging',
            'availableOptions',
            'shippingMethods',
        ]), $request)->paginate(
            perPage: $request->perPage(),
            pageName: 'page',
            page: $request->pageNumber(),
        );

        return sendResponse(
            true,
            __('common.success'),
            ProductResource::collection($Product),
            HttpStatus::HTTP_OK
        );
    }

    public function store(ManufacturerStoreProductRequest $request)
    {
        $validate = $request->validated();

        // dd($validate);
        try {

            $product = DB::transaction(function () use ($validate, $request) {

                $id = $request->user()->id;

                $slug = Str::slug($validate['name']);
                $originalSlug = $slug;
                $count = 1;

                while (Product::where('slug', $slug)->exists()) {
                    $slug = $originalSlug.'-'.$count;
                    $count++;
                }

                $product = Product::create([
                    'user_id' => $id,
                    'slug' => $slug,
                    'name' => $validate['name'],
                    'description' => $validate['description'],
                    'industry_id' => $validate['category_id'],
                    'sub_category_id' => $validate['sub_category_id'],
                    'keywords' => json_encode($validate['keywords']),
                    'status' => ProductStatusEnum::ACTIVE->value,
                ]);

                $product->autoTranslate(
                    [
                        'name' => $validate['name'],
                        'description' => $validate['description'],
                    ],
                    $request->locale ?? null
                );

                // Handle Images
                if ($validate['product_images']) {

                    $images = $validate['product_images'];

                    $imageData = [];
                    foreach ($images as $image) {

                        if ($image instanceof UploadedFile) {

                            $fileName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)
                                .'_'.uniqid().'.'
                                .$image->getClientOriginalExtension();

                            Storage::disk('public')->put('products/'.$fileName, file_get_contents($image));

                            $imageData[] = [
                                'image_path' => 'products/'.$fileName,
                                'product_id' => $product->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }

                    // Insert all images at once
                    if (! empty($imageData)) {
                        ProductImage::insert($imageData);
                    }
                }

                // Pricing and Quantities

                $product->pricingQuantities()->create([
                    'min_price' => $validate['min_price'],
                    'max_price' => $validate['max_price'],
                    'minimum_order_quantity' => $validate['minimum_order_quantity'],
                    'unit' => $validate['unit'],
                    'lead_time' => $validate['lead_time'],
                    'currency_id' => $validate['currency_id'],
                    'production_capacity' => $validate['production_capacity'],
                    'production_duration' => $validate['production_duration'],
                    'production_unit' => $validate['production_unit'],
                    'production_capacity' => $validate['production_capacity'],
                ]);
                // Product Specification
                if ($validate['product_specifications']) {

                    $specifications = $validate['product_specifications'];

                    foreach ($specifications as $specification) {

                        $specificationData = [
                            'specification_title' => $specification['specification_title'],
                            'specification_value' => $specification['specification_value'],
                        ];

                        $specificationModel = $product->specifications()->create($specificationData);

                        $specificationModel->autoTranslate(
                            [
                                'specification_title' => $specification['specification_title'],
                                'specification_value' => $specification['specification_value'],
                            ],
                            $request->locale ?? null
                        );
                    }
                }

                // Key Features

                if ($validate['key_features']) {

                    $keyFeatures = $validate['key_features'];
                    foreach ($keyFeatures as $key) {
                        $productKeyFeatureModel = $product->productKeyFeatures()->create([
                            'key_feature' => $key,
                        ]);

                        $productKeyFeatureModel->autoTranslate(
                            [
                                'key_feature' => $key,
                            ],
                            $request->locale ?? null
                        );
                    }
                }

                // Customizations Options

                if ($validate['customize_options']) {

                    $customOptions = $validate['customize_options'];

                    foreach ($customOptions as $customOption) {
                        $customOptionData = [
                            'option' => $customOption,
                        ];

                        $customizeModel = $product->customizationOptions()->create($customOptionData);
                        $customizeModel->autoTranslate(
                            [
                                'option' => $customOption,
                            ],
                            $request->locale ?? null
                        );
                    }
                }

                // Shipping & Packaging

                $shippingPackaging = $product->shippingPackaging()->create([
                    'packaging_type' => $validate['packaging_type'],
                    'port_of_loading' => $validate['port_of_loading'],
                    'packaging_dimensions' => $validate['packaging_dimensions'],
                    'packaging_weight' => $validate['packaging_weight'],
                    'packaging_cost_per_unit' => $validate['packaging_cost_per_unit'],
                    'packaging_description' => $validate['packaging_description'],
                ]);

                $shippingPackaging->autoTranslate(
                    [
                        'packaging_type' => $validate['packaging_type'],
                        'port_of_loading' => $validate['port_of_loading'],
                        'packaging_dimensions' => $validate['packaging_dimensions'],
                        'packaging_weight' => $validate['packaging_weight'],
                        'packaging_description' => $validate['packaging_description'],
                    ],
                    $request->locale ?? null
                );

                // Available Shipping Methods

                $product->shippingMethods()->attach($validate['shipping_methods']);

                $broschure_path = null;
                if ($image = $validate['product_broschure']) {
                    if ($image instanceof UploadedFile) {

                        $fileName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)
                            .'_'.uniqid().'.'
                            .$image->getClientOriginalExtension();

                        Storage::disk('public')->put('products/broshure/'.$fileName, file_get_contents($image));

                        $broschure_path = 'products/broshure/'.$fileName;
                    }
                }

                // Available Options

                $availableOption = $product->availableOptions()->create([
                    'sample_available' => $validate['sample_available'],
                    'sample_price' => $validate['sample_price'],
                    'customization_available' => $validate['customization_available'],
                    'customization_detail' => $validate['customization_detail'],
                    'product_broschure' => $broschure_path,
                ]);

                $availableOption->autoTranslate(
                    [
                        'customization_detail' => $validate['customization_detail'],
                    ],
                    $request->locale ?? null
                );

                return $product->load([
                    'user',
                    'currency',
                    'category',
                    'subCategory',
                    'images',
                    'pricingQuantities.currency',
                    'specifications',
                    'productKeyFeatures',
                    'customizationOptions',
                    'shippingPackaging',
                    'availableOptions',
                    'shippingMethods',
                ]);
            });

            return sendResponse(
                true,
                __('common.success'),
                new ProductResource($product),
                // $product,
                HttpStatus::HTTP_CREATED
            );
        } catch (\Exception $e) {
            Log::error('Error creating product: '.$e);

            return sendResponse(
                false,
                __('common.error'),
                null,
                HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function show(Request $request, $slug)
    {

        $product = Product::where('slug', $slug)->where('user_id', $request->user()->id)->first();

        if (! $product) {
            return sendResponse(
                false,
                __('common.not_found'),
                null,
                HttpStatus::HTTP_NOT_FOUND
            );
        }

        // Load all relationships
        $product->load([
            'user',
            'currency',
            'category',
            'subCategory',
            'images',
            'pricingQuantities.currency',
            'specifications',
            'productKeyFeatures',
            'customizationOptions',
            'shippingPackaging',
            'availableOptions',
            'shippingMethods',
        ]);

        return sendResponse(
            true,
            __('common.success'),
            new ProductResource($product),
            HttpStatus::HTTP_OK
        );
    }

    public function update(ManufacturerUpdateProductRequest $request, $id)
    {
        $validate = $request->validated();

        $product = Product::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (! $product) {
            return sendResponse(
                false,
                __('common.not_found'),
                null,
                HttpStatus::HTTP_NOT_FOUND
            );
        }

        try {
            $product = DB::transaction(function () use ($validate, $request, $product) {

                // Generate new slug if name changed
                if (isset($validate['name']) && $validate['name'] !== $product->name) {
                    $slug = Str::slug($validate['name']);
                    $originalSlug = $slug;
                    $count = 1;

                    while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                        $slug = $originalSlug.'-'.$count;
                        $count++;
                    }
                    $validate['slug'] = $slug;
                }

                // Update basic product info
                $productData = [
                    'name' => $validate['name'] ?? $product->name,
                    'description' => $validate['description'] ?? $product->description,
                    'industry_id' => $validate['category_id'] ?? $product->industry_id,
                    'sub_category_id' => $validate['sub_category_id'] ?? $product->sub_category_id,
                    'keywords' => isset($validate['keywords']) ? json_encode($validate['keywords']) : $product->keywords,
                    'status' => $validate['status'] ?? $product->status,
                ];

                if (isset($validate['slug'])) {
                    $productData['slug'] = $validate['slug'];
                }

                $product->update($productData);

                // Handle translation if name or description changed
                $translatableChanged = Arr::only($validate, $product->translatableFields());

                if (! empty($translatableChanged)) {
                    $product->autoTranslate(
                        sourceData: $translatableChanged,
                        sourceLocale: $request->locale ?? null,
                    );
                }

                // Handle Image Management
                // Delete specified images
                if (! empty($validate['delete_images'])) {
                    $imagesToDelete = ProductImage::whereIn('id', $validate['delete_images'])
                        ->where('product_id', $product->id)
                        ->get();

                    foreach ($imagesToDelete as $image) {
                        if (Storage::disk('public')->exists($image->image_path)) {
                            Storage::disk('public')->delete($image->image_path);
                        }
                        $image->delete();
                    }
                }

                // Add new images
                if (! empty($validate['product_images'])) {
                    $imageData = [];
                    foreach ($validate['product_images'] as $image) {
                        if ($image instanceof UploadedFile) {
                            $fileName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)
                                .'_'.uniqid().'.'
                                .$image->getClientOriginalExtension();

                            Storage::disk('public')->put('products/'.$fileName, file_get_contents($image));

                            $imageData[] = [
                                'image_path' => 'products/'.$fileName,
                                'product_id' => $product->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }

                    if (! empty($imageData)) {
                        ProductImage::insert($imageData);
                    }
                }

                // Update Pricing and Quantities
                if (
                    isset($validate['min_price']) || isset($validate['max_price']) ||
                    isset($validate['minimum_order_quantity']) || isset($validate['unit']) ||
                    isset($validate['lead_time']) || isset($validate['currency_id']) ||
                    isset($validate['production_capacity']) || isset($validate['production_duration']) ||
                    isset($validate['production_unit'])
                ) {

                    $pricingData = [
                        'min_price' => $validate['min_price'] ?? $product->pricingQuantities->min_price,
                        'max_price' => $validate['max_price'] ?? $product->pricingQuantities->max_price,
                        'minimum_order_quantity' => $validate['minimum_order_quantity'] ?? $product->pricingQuantities->minimum_order_quantity,
                        'unit' => $validate['unit'] ?? $product->pricingQuantities->unit,
                        'lead_time' => $validate['lead_time'] ?? $product->pricingQuantities->lead_time,
                        'currency_id' => $validate['currency_id'] ?? $product->pricingQuantities->currency_id,
                        'production_capacity' => $validate['production_capacity'] ?? $product->pricingQuantities->production_capacity,
                        'production_duration' => $validate['production_duration'] ?? $product->pricingQuantities->production_duration,
                        'production_unit' => $validate['production_unit'] ?? $product->pricingQuantities->production_unit,
                    ];

                    $product->pricingQuantities()->update($pricingData);
                }

                // Update Product Specifications
                if (isset($validate['product_specifications'])) {
                    // Delete existing specifications
                    $product->specifications()->delete();

                    // Create new specifications
                    $specifications = $validate['product_specifications'];
                    foreach ($specifications as $specification) {
                        $specificationData = [
                            'specification_title' => $specification['specification_title'],
                            'specification_value' => $specification['specification_value'],
                        ];

                        $specificationModel = $product->specifications()->create($specificationData);

                        // Handle Translations
                        $specificationModel->autoTranslate(
                            [
                                'specification_title' => $specification['specification_title'],
                                'specification_value' => $specification['specification_value'],
                            ],
                            $request->locale ?? null
                        );
                    }
                }

                // Update Key Features
                if (isset($validate['key_features'])) {
                    // Delete existing key features
                    $product->productKeyFeatures()->delete();

                    // Create new key features
                    $keyFeatures = $validate['key_features'];
                    foreach ($keyFeatures as $key) {
                        $productKeyFeatureModel = $product->productKeyFeatures()->create([
                            'key_feature' => $key,
                        ]);

                        $productKeyFeatureModel->autoTranslate(
                            [
                                'key_feature' => $key,
                            ],
                            $request->locale ?? null
                        );
                    }
                }

                // Update Customization Options
                if (isset($validate['customize_options'])) {
                    // Delete existing customization options
                    $product->customizationOptions()->delete();

                    // Create new customization options
                    $customOptions = $validate['customize_options'];
                    foreach ($customOptions as $customOption) {
                        $customOptionData = [
                            'option' => $customOption,
                        ];

                        $customizeModel = $product->customizationOptions()->create($customOptionData);

                        $customizeModel->autoTranslate(
                            [
                                'option' => $customOption,
                            ],
                            $request->locale ?? null
                        );
                    }
                }

                // Update Shipping & Packaging
                if (
                    isset($validate['packaging_type']) || isset($validate['port_of_loading']) ||
                    isset($validate['packaging_dimensions']) || isset($validate['packaging_weight']) ||
                    isset($validate['packaging_cost_per_unit']) || isset($validate['packaging_description'])
                ) {

                    $shippingData = [
                        'packaging_type' => $validate['packaging_type'] ?? $product->shippingPackaging?->packaging_type,
                        'port_of_loading' => $validate['port_of_loading'] ?? $product->shippingPackaging?->port_of_loading,
                        'packaging_dimensions' => $validate['packaging_dimensions'] ?? $product->shippingPackaging?->packaging_dimensions,
                        'packaging_weight' => $validate['packaging_weight'] ?? $product->shippingPackaging?->packaging_weight,
                        'packaging_cost_per_unit' => $validate['packaging_cost_per_unit'] ?? $product->shippingPackaging?->packaging_cost_per_unit,
                        'packaging_description' => $validate['packaging_description'] ?? $product->shippingPackaging?->packaging_description,
                    ];

                    $product->shippingPackaging()->update($shippingData);

                    if ($product->shippingPackaging) {
                        $translatableChangedShippingPackaging = Arr::only(
                            $validate,
                            $product->shippingPackaging->translatableFields()
                        );

                        if (! empty($translatableChangedShippingPackaging)) {
                            $product->shippingPackaging->autoTranslate(
                                $translatableChangedShippingPackaging,
                                $request->locale ?? null
                            );
                        }
                    }
                }

                // Update Shipping Methods
                if (isset($validate['shipping_methods'])) {
                    $product->shippingMethods()->sync($validate['shipping_methods']);
                }

                // Handle Product Brochure
                $broschure_path = $product->availableOptions?->product_broschure ?? null;
                if (isset($validate['product_broschure']) && $validate['product_broschure'] instanceof UploadedFile) {
                    // Delete old brochure if exists
                    if ($broschure_path && Storage::disk('public')->exists($broschure_path)) {
                        Storage::disk('public')->delete($broschure_path);
                    }

                    $fileName = pathinfo($validate['product_broschure']->getClientOriginalName(), PATHINFO_FILENAME)
                        .'_'.uniqid().'.'
                        .$validate['product_broschure']->getClientOriginalExtension();

                    Storage::disk('public')->put('products/broshure/'.$fileName, file_get_contents($validate['product_broschure']));

                    $broschure_path = 'products/broshure/'.$fileName;
                }

                // Update Available Options
                if (
                    isset($validate['sample_available']) || isset($validate['sample_price']) ||
                    isset($validate['customization_available']) || isset($validate['customization_detail']) ||
                    isset($validate['product_broschure'])
                ) {

                    $availableOptionsData = [
                        'sample_available' => $validate['sample_available'] ?? $product->availableOptions?->sample_available,
                        'sample_price' => $validate['sample_price'] ?? $product->availableOptions?->sample_price,
                        'customization_available' => $validate['customization_available'] ?? $product->availableOptions?->customization_available,
                        'customization_detail' => $validate['customization_detail'] ?? $product->availableOptions?->customization_detail,
                        'product_broschure' => $broschure_path,
                    ];

                    $product->availableOptions()->update($availableOptionsData);
                    if ($product->availableOptions) {
                        $translatableChangedOption = Arr::only(
                            $validate,
                            $product->availableOptions->translatableFields()
                        );

                        if (! empty($translatableChangedOption)) {
                            $product->availableOptions->autoTranslate(
                                $translatableChangedOption,
                                $request->locale ?? null
                            );
                        }
                    }
                }

                return $product->load([
                    'user',
                    'currency',
                    'category',
                    'subCategory',
                    'images',
                    'pricingQuantities.currency',
                    'specifications',
                    'productKeyFeatures',
                    'customizationOptions',
                    'shippingPackaging',
                    'availableOptions',
                    'shippingMethods',
                ]);
            });

            return sendResponse(
                true,
                __('common.updated'),
                new ProductResource($product),
                HttpStatus::HTTP_OK
            );
        } catch (\Exception $e) {
            Log::error('Error updating product: '.$e);

            return sendResponse(
                false,
                __('common.error'),
                null,
                HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function destroy(Request $request, $id)
    {

        $product = Product::where('id', $id)->where('user_id', $request->user()->id)->first();

        if (! $product) {
            return sendResponse(
                false,
                __('common.not_found'),
                null,
                HttpStatus::HTTP_NOT_FOUND
            );
        }

        $product->load(['images', 'availableOptions']);

        $images = $product->images;
        $availableOptions = $product->availableOptions;

        $deleted = $product->delete();

        if ($deleted) {
            // Delete images
            if ($images->count() > 0) {
                foreach ($images as $image) {
                    if (Storage::disk('public')->exists($image->image_path)) {
                        Storage::disk('public')->delete($image->image_path);
                    }
                }
            }

            // Delete product brochure if exists
            if ($availableOptions && $availableOptions->product_broschure) {
                if (Storage::disk('public')->exists($availableOptions->product_broschure)) {
                    Storage::disk('public')->delete($availableOptions->product_broschure);
                }
            }
        }

        return sendResponse(
            true,
            __('common.deleted'),
            null,
            HttpStatus::HTTP_OK
        );
    }

    public function changeStatus(Request $request, $id)
    {

        $product = Product::where('id', $id)->where('user_id', $request->user()->id)->first();

        $validated = $request->validate([
            'status' => 'required|in:active,inactive,draft',
        ]);
        if (! $product) {
            return sendResponse(
                false,
                __('common.not_found'),
                null,
                HttpStatus::HTTP_NOT_FOUND
            );
        }

        $product->update([
            'status' => $validated['status'],
        ]);

        return sendResponse(
            true,
            __('common.updated'),
            new ProductResource($product),
            HttpStatus::HTTP_OK
        );
    }

    public function duplicateToDraft(Request $request, $id)
    {
        $product = DB::transaction(function () use ($request, $id) {

            $product = Product::with([
                'images',
                'pricingQuantities',
                'specifications',
                'productKeyFeatures',
                'customizationOptions',
                'shippingPackaging',
                'availableOptions',
                'shippingMethods',
            ])->where('id', $id)->where('user_id', $request->user()->id)->first();

            if (! $product) {
                return sendResponse(
                    false,
                    __('common.not_found'),
                    null,
                    HttpStatus::HTTP_NOT_FOUND
                );
            }

            // Clone product
            $new = $product->replicate();

            $slug = Str::slug($new->name);
            $originalSlug = $slug;
            $count = 1;

            while (Product::where('slug', $slug)->exists()) {
                $slug = $originalSlug.'-'.$count;
                $count++;
            }

            $new->slug = $slug;
            $new->status = 'draft';
            $new->push();

            // Images (bulk)
            $images = $product->images->map(fn ($img) => [])->toArray();

            $images = [];
            foreach ($product->images as $img) {

                $oldPath = $img->image_path;

                if (Storage::disk('public')->exists($oldPath)) {

                    $newFileName = pathinfo($oldPath, PATHINFO_FILENAME)
                        .'_'.uniqid().'.'
                        .pathinfo($oldPath, PATHINFO_EXTENSION);

                    $newPath = 'products/'.$newFileName;

                    Storage::disk('public')->put(
                        $newPath,
                        Storage::disk('public')->get($oldPath)
                    );

                    $images[] = [
                        'image_path' => $newPath,
                        'product_id' => $new->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (! empty($images)) {
                ProductImage::insert($images);
            }

            // Pricing
            if ($product->pricingQuantities) {
                $new->pricingQuantities()->create(
                    $product->pricingQuantities->toArray()
                );
            }

            // Simple hasMany clone helper
            foreach (['specifications', 'productKeyFeatures', 'customizationOptions'] as $relation) {
                foreach ($product->$relation as $item) {
                    $new->$relation()->create($item->toArray());
                }
            }

            // Single relations
            optional($product->shippingPackaging)->replicate()->fill(['product_id' => $new->id])->save();
            optional($product->availableOptions)->replicate()->fill(['product_id' => $new->id])->save();

            if (! empty($product->availableOptions->product_brochure)) {
                if (Storage::disk('public')->exists($product->availableOptions->product_brochure)) {

                    $newFileName = pathinfo($product->availableOptions->product_brochure, PATHINFO_FILENAME)
                        .'_'.uniqid().'.'
                        .pathinfo($product->availableOptions->product_brochure, PATHINFO_EXTENSION);

                    $newPath = 'products/'.$newFileName;

                    Storage::disk('public')->put(
                        $newPath,
                        Storage::disk('public')->get($product->availableOptions->product_brochure)
                    );

                    $new->availableOptions()->update([
                        'product_brochure' => $newPath,
                    ]);
                } else {
                    $new->availableOptions()->update([
                        'product_brochure' => null,
                    ]);
                }
            }
            // Many-to-many
            $new->shippingMethods()->sync(
                $product->shippingMethods->pluck('id')
            );

            return $new;
        });

        $product->load([
            'images',
            'pricingQuantities',
            'specifications',
            'productKeyFeatures',
            'customizationOptions',
            'shippingPackaging',
            'availableOptions',
            'shippingMethods',
        ]);

        return sendResponse(
            true,
            __('common.duplicated_successfully'),
            new ProductResource($product),
            statusCode: HttpStatus::HTTP_OK
        );
    }
}
