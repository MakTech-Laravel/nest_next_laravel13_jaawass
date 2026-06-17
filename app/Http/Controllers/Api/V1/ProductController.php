<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PublicProductIndexRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Rules\EnabledCurrencyCode;
use App\Services\Currency\PersistedListingCurrencyResolver;
use App\Services\Product\ProductCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ProductController extends Controller
{
    public function __construct(
        private readonly PersistedListingCurrencyResolver $persistedListingCurrency,
        private readonly ProductCatalogService $productCatalogService,
    ) {}

    /* ------------------------------------------------------------------
    |  GET /api/v1/products
    |
    |  List all products with translated name/description for the
    |  requested locale. Translations are eager-loaded in one query.
    | ------------------------------------------------------------------ */

    public function index(PublicProductIndexRequest $request): JsonResponse
    {
        $products = $this->productCatalogService->paginatePublicProducts($request);

        return sendResponse(
            status: true,
            message: __('api.products_fetched_successfully'),
            data: ProductResource::collection($products),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function byCategory(Request $request, int $categoryId): JsonResponse
    {
        $products = $this->productCatalogService->getPublicProductsByCategory($categoryId);

        return sendResponse(
            status: true,
            message: __('api.products_fetched_successfully'),
            data: ProductResource::collection($products),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function bySubCategory(Request $request, int $subCategoryId): JsonResponse
    {
        $products = $this->productCatalogService->getPublicProductsBySubCategory($subCategoryId);

        return sendResponse(
            status: true,
            message: __('api.products_fetched_successfully'),
            data: ProductResource::collection($products),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    /* ------------------------------------------------------------------
    |  GET /api/v1/products/{product}
    | ------------------------------------------------------------------ */

    public function show(Request $request, Product $product): JsonResponse
    {
        $product->load([
            ...$this->productCatalogService->eagerRelationsForPublicProduct(withReviews: true),
        ]);

        return sendResponse(
            status: true,
            message: __('api.product_fetched_successfully'),
            data: new ProductResource($product),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    /* ------------------------------------------------------------------
    |  POST /api/v1/products
    |
    |  Frontend sends content in ANY language.
    |  We store it as-is and queue auto-translation for all other locales.
    | ------------------------------------------------------------------ */

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'currency_code' => ['nullable', 'string', 'size:3', new EnabledCurrencyCode],
            // Optional: the locale the user submitted content in.
            // Omit to let Google auto-detect the language.
            'locale' => ['nullable', 'string', 'max:10'],
        ]);

        $bodyCurrencyCode = $validated['currency_code'] ?? null;
        unset($validated['currency_code']);
        $currencyId = $this->persistedListingCurrency->resolve($bodyCurrencyCode);

        $product = Product::create([
            'user_id' => 1,
            'currency_id' => $currencyId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'quantity' => $validated['quantity'],
            'image' => $validated['image'] ?? null,
            'status' => $validated['status'],
        ]);

        // Dispatch translation job — non-blocking when queue is enabled
        $product->autoTranslate(
            sourceData: [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? '',
            ],
            sourceLocale: $validated['locale'] ?? null, // null = auto-detect
        );

        $product->load($this->productCatalogService->eagerRelationsForPublicProduct());

        return sendResponse(
            status: true,
            message: __('api.product_created_successfully'),
            data: new ProductResource($product),
            statusCode: HttpStatus::HTTP_CREATED
        );
    }

    /* ------------------------------------------------------------------
    |  PUT /api/v1/products/{product}
    | ------------------------------------------------------------------ */

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'image' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string'],
            'currency_code' => ['sometimes', 'nullable', 'string', 'size:3', new EnabledCurrencyCode],
            'locale' => ['nullable', 'string', 'max:10'],
        ]);

        if (array_key_exists('currency_code', $validated)) {
            $bodyCurrencyCode = $validated['currency_code'];
            unset($validated['currency_code']);
            $validated['currency_id'] = $this->persistedListingCurrency->resolve($bodyCurrencyCode);
        }

        $product->update($validated);

        // Re-translate only if a translatable field actually changed
        $translatableChanged = Arr::only($validated, $product->translatableFields());

        if (! empty($translatableChanged)) {
            $product->autoTranslate(
                sourceData: $translatableChanged,
                sourceLocale: $validated['locale'] ?? null,
            );
        }

        $product->load($this->productCatalogService->eagerRelationsForPublicProduct());

        return sendResponse(
            status: true,
            message: __('api.product_updated_successfully'),
            data: new ProductResource($product),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    /* ------------------------------------------------------------------
    |  DELETE /api/v1/products/{product}
    | ------------------------------------------------------------------ */

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return sendResponse(
            status: true,
            message: __('api.product_deleted_successfully'),
            statusCode: HttpStatus::HTTP_OK
        );
    }
}
