<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Api\V1\CatalogStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PublicProductIndexRequest;
use App\Http\Requests\Api\V1\PublicSupplierIndexRequest;
use App\Http\Resources\Api\V1\Manufacturer\CatalogResource;
use App\Http\Resources\Api\V1\Manufacturer\CertificateResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Resources\Api\V1\ProductReviewResource;
use App\Http\Resources\Api\V1\PublicSupplierDetailResource;
use App\Http\Resources\Api\V1\PublicSupplierResource;
use App\Models\Review;
use App\Models\User;
use App\Services\Product\ProductCatalogService;
use App\Services\Supplier\PublicSupplierCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class PublicSupplierController extends Controller
{
    public function __construct(
        private readonly PublicSupplierCatalogService $supplierCatalogService,
        private readonly ProductCatalogService $productCatalogService,
    ) {}

    public function index(PublicSupplierIndexRequest $request): JsonResponse
    {
        $ids = $request->supplierIds();

        if ($ids !== []) {
            $suppliers = $this->supplierCatalogService->getPublicSuppliersByIds($ids);

            return sendResponse(
                status: true,
                message: __('api.suppliers_fetched_successfully'),
                data: PublicSupplierResource::collection($suppliers),
                statusCode: HttpStatus::HTTP_OK
            );
        }

        $suppliers = $this->supplierCatalogService->paginatePublicSuppliers($request);

        return sendResponse(
            status: true,
            message: __('api.suppliers_fetched_successfully'),
            data: PublicSupplierResource::collection($suppliers),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function show(Request $request, User $supplier): JsonResponse
    {
        $supplier->load($this->supplierCatalogService->eagerRelationsForDetail());

        return sendResponse(
            status: true,
            message: __('api.supplier_fetched_successfully'),
            data: new PublicSupplierDetailResource($supplier),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function products(PublicProductIndexRequest $request, User $supplier): JsonResponse
    {
        $request->merge(['supplier_id' => $supplier->id]);

        $products = $this->productCatalogService->paginatePublicProducts($request);

        return sendResponse(
            status: true,
            message: __('api.products_fetched_successfully'),
            data: ProductResource::collection($products),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function reviews(Request $request, User $supplier): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $reviews = Review::query()
            ->where('user_id', $supplier->id)
            ->with(['reviewer.company', 'product', 'order'])
            ->latest('id')
            ->paginate($perPage);

        return sendResponse(
            status: true,
            message: __('api.supplier_reviews_fetched_successfully'),
            data: [
                'review_stats' => $this->supplierCatalogService->reviewStatsForSupplier($supplier),
                'reviews' => ProductReviewResource::collection($reviews),
            ],
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function catalogs(Request $request, User $supplier): JsonResponse
    {
        $catalogs = $supplier->catalogs()
            ->where('status', CatalogStatusEnum::ACTIVE->value)
            ->orderByDesc('updated_at')
            ->get();

        return sendResponse(
            status: true,
            message: __('api.supplier_catalogs_fetched_successfully'),
            data: CatalogResource::collection($catalogs),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function certifications(Request $request, User $supplier): JsonResponse
    {
        $company = $supplier->company;
        $locale = $request->query('locale') ?? app()->getLocale();

        $profileCerts = [];
        if ($company !== null) {
            $localized = $company->localizedData($locale);
            $raw = $localized['certifications'] ?? $company->certifications;

            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $profileCerts = is_array($decoded) ? $decoded : [];
            } elseif (is_array($raw)) {
                $profileCerts = $raw;
            }
        }

        $uploaded = $supplier->certificates()
            ->valid()
            ->with('certificateType')
            ->orderByDesc('id')
            ->get();

        return sendResponse(
            status: true,
            message: __('api.supplier_certifications_fetched_successfully'),
            data: [
                'profile_certifications' => array_values($profileCerts),
                'uploaded_certificates' => CertificateResource::collection($uploaded),
            ],
            statusCode: HttpStatus::HTTP_OK
        );
    }
}
