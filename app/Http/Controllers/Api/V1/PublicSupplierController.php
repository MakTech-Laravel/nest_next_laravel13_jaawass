<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Api\V1\CatalogStatusEnum;
use App\Enums\DashboardEventType;
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
use App\Services\Dashboard\EventTrackerService;
use App\Services\Product\ProductCatalogService;
use App\Services\Supplier\PublicSupplierCatalogService;
use App\Support\Countries\CountryMapCatalog;
use App\Support\Countries\ViewerCountryResolver;
use App\Support\ExportMarkets\ManufacturerExportMarketVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class PublicSupplierController extends Controller
{
    public function __construct(
        private readonly PublicSupplierCatalogService $supplierCatalogService,
        private readonly ProductCatalogService $productCatalogService,
        private readonly EventTrackerService $eventTracker,
        private readonly ViewerCountryResolver $viewerCountryResolver,
        private readonly ManufacturerExportMarketVisibility $exportMarketVisibility,
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
        $viewerCountryCode = $this->viewerCountryResolver->resolveFromRequest($request);

        if (! $this->exportMarketVisibility->supplierVisibleToCountry((int) $supplier->id, $viewerCountryCode)) {
            abort(HttpStatus::HTTP_NOT_FOUND);
        }

        $supplier->load($this->supplierCatalogService->eagerRelationsForDetail());

        $viewer = $request->user();
        if ($viewer !== null) {
            $this->eventTracker->trackOnceWithinWindow(
                eventType: DashboardEventType::SupplierViewed,
                actor: $viewer,
                entityType: 'supplier',
                entityId: (int) $supplier->id,
                counterparty: $supplier,
                metadata: [
                    'slug' => $supplier->company?->slug,
                ],
                windowMinutes: 30,
            );
        }

        return sendResponse(
            status: true,
            message: __('api.supplier_fetched_successfully'),
            data: new PublicSupplierDetailResource($supplier),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function mapCountries(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'group' => ['sometimes', 'string', 'in:Africa,Americas,Asia,Europe,Oceania'],
            'search' => ['sometimes', 'string', 'max:120'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:250'],
        ]);

        $group = $validated['group'] ?? null;
        $search = isset($validated['search']) ? strtolower(trim((string) $validated['search'])) : null;
        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 25);

        $countryCounts = $this->supplierCatalogService
            ->publicSupplierBaseQuery()
            ->join('companies', 'companies.user_id', '=', 'users.id')
            ->whereNotNull('companies.country')
            ->where('companies.country', '!=', '')
            ->selectRaw('LOWER(TRIM(companies.country)) as country_key, MIN(TRIM(companies.country)) as country_name, COUNT(DISTINCT users.id) as suppliers_count')
            ->groupBy('country_key')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (string) $row->country_key => [
                    'country' => (string) $row->country_name,
                    'suppliers_count' => (int) $row->suppliers_count,
                ],
            ]);

        $exportCountsByCode = $this->exportMarketVisibility
            ->exportSupplierCountsByCode($this->supplierCatalogService->publicSupplierBaseQuery());

        $predefined = collect($this->predefinedCountryMapData())
            ->when($group !== null, fn (Collection $items) => $items->where('group', $group))
            ->when($search !== null && $search !== '', function (Collection $items) use ($search): Collection {
                return $items->filter(
                    fn (array $country): bool => str_contains(strtolower($country['country']), $search)
                        || str_contains(strtolower($country['country_code']), $search)
                        || str_contains(strtolower($country['subregion'] ?? ''), $search)
                );
            })
            ->values();

        $countries = $predefined
            ->map(function (array $country) use ($countryCounts, $exportCountsByCode): array {
                $key = strtolower(trim($country['country']));
                $matched = $countryCounts->get($key);
                $exportCount = (int) ($exportCountsByCode[strtoupper($country['country_code'])] ?? 0);

                return $this->withCountryFlag([
                    'name' => $country['name'],
                    'country' => $country['country'],
                    'country_code' => $country['country_code'],
                    'group' => $country['group'],
                    'subregion' => $country['subregion'] ?? null,
                    'coordinates' => $country['coordinates'] ?? ['lat' => null, 'lng' => null],
                    'suppliers_count' => (int) ($matched['suppliers_count'] ?? 0),
                    'export_suppliers_count' => $exportCount,
                    'has_suppliers' => isset($matched['suppliers_count']) && (int) $matched['suppliers_count'] > 0,
                    'has_export_suppliers' => $exportCount > 0,
                ]);
            })
            ->values();

        // Include any country already in DB but not in predefined list.
        $extraCountries = $countryCounts
            ->filter(fn (array $entry, string $key): bool => ! $predefined->contains(
                fn (array $pre) => strtolower(trim($pre['country'])) === $key
            ))
            ->map(fn (array $entry): array => $this->withCountryFlag([
                'name' => $entry['country'],
                'country' => $entry['country'],
                'country_code' => null,
                'group' => $group ?? 'Other',
                'subregion' => null,
                'coordinates' => ['lat' => null, 'lng' => null],
                'suppliers_count' => $entry['suppliers_count'],
                'export_suppliers_count' => 0,
                'has_suppliers' => $entry['suppliers_count'] > 0,
                'has_export_suppliers' => false,
            ]))
            ->values();

        $merged = $countries
            ->concat($extraCountries)
            ->sortBy('country')
            ->values();

        $total = $merged->count();
        $countriesPage = $merged
            ->forPage($page, $perPage)
            ->values();

        return sendResponse(
            status: true,
            message: __('api.supplier_map_fetched_successfully'),
            data: [
                'countries' => $countriesPage,
                'country_code_groups' => $this->groupByCountryCodePrefix($merged),
                'total_countries' => $total,
                'countries_with_suppliers' => $merged->where('suppliers_count', '>', 0)->count(),
                'total_suppliers' => (int) $merged->sum('suppliers_count'),
                'filters' => [
                    'group' => $group,
                    'search' => $search,
                ],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => (int) ceil($total / max($perPage, 1)),
                ],
            ],
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function mapCountryGroups(): JsonResponse
    {
        $countryCounts = $this->supplierCatalogService
            ->publicSupplierBaseQuery()
            ->join('companies', 'companies.user_id', '=', 'users.id')
            ->whereNotNull('companies.country')
            ->where('companies.country', '!=', '')
            ->selectRaw('LOWER(TRIM(companies.country)) as country_key, COUNT(DISTINCT users.id) as suppliers_count')
            ->groupBy('country_key')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (string) $row->country_key => (int) $row->suppliers_count,
            ]);

        $grouped = collect($this->predefinedCountryMapData())
            ->groupBy('group')
            ->map(function (Collection $countries, string $group) use ($countryCounts): array {
                $supplierCount = $countries->sum(function (array $country) use ($countryCounts): int {
                    $key = strtolower(trim($country['country']));

                    return (int) ($countryCounts[$key] ?? 0);
                });

                return [
                    'group' => $group,
                    'country_count' => $countries->count(),
                    'countries_with_suppliers' => $countries->filter(function (array $country) use ($countryCounts): bool {
                        $key = strtolower(trim($country['country']));

                        return (int) ($countryCounts[$key] ?? 0) > 0;
                    })->count(),
                    'suppliers_count' => $supplierCount,
                ];
            })
            ->sortBy('group')
            ->values();

        return sendResponse(
            status: true,
            message: __('api.supplier_map_groups_fetched_successfully'),
            data: [
                'total_groups' => $grouped->count(),
                'groups' => $grouped,
            ],
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function mapTopManufacturerCountries(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'group' => ['sometimes', 'string', 'in:Africa,Americas,Asia,Europe,Oceania'],
            'search' => ['sometimes', 'string', 'max:120'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:250'],
        ]);

        $group = $validated['group'] ?? null;
        $search = isset($validated['search']) ? strtolower(trim((string) $validated['search'])) : null;
        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 15);

        $countryMeta = collect($this->predefinedCountryMapData())
            ->keyBy(fn (array $country): string => strtolower(trim($country['country'])));

        $rows = $this->supplierCatalogService
            ->publicSupplierBaseQuery()
            ->join('companies', 'companies.user_id', '=', 'users.id')
            ->whereNotNull('companies.country')
            ->where('companies.country', '!=', '')
            ->selectRaw('LOWER(TRIM(companies.country)) as country_key, MIN(TRIM(companies.country)) as country, COUNT(DISTINCT users.id) as manufacturers_count')
            ->groupBy('country_key')
            ->get()
            ->map(function ($row) use ($countryMeta): array {
                $key = (string) $row->country_key;
                $meta = $countryMeta->get($key);

                return $this->withCountryFlag([
                    'country' => (string) $row->country,
                    'country_code' => $meta['country_code'] ?? null,
                    'group' => $meta['group'] ?? 'Other',
                    'subregion' => $meta['subregion'] ?? null,
                    'manufacturers_count' => (int) $row->manufacturers_count,
                ]);
            })
            ->when($group !== null, fn (Collection $items) => $items->where('group', $group))
            ->when($search !== null && $search !== '', function (Collection $items) use ($search): Collection {
                return $items->filter(
                    fn (array $country): bool => str_contains(strtolower($country['country']), $search)
                        || str_contains(strtolower((string) ($country['country_code'] ?? '')), $search)
                );
            })
            ->sortByDesc('manufacturers_count')
            ->values();

        $total = $rows->count();
        $data = $rows->forPage($page, $perPage)->values();

        return sendResponse(
            status: true,
            message: __('api.supplier_map_top_countries_fetched_successfully'),
            data: [
                'countries' => $data,
                'filters' => [
                    'group' => $group,
                    'search' => $search,
                ],
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => (int) ceil($total / max($perPage, 1)),
                ],
            ],
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
            ->publiclyVisible()
            ->with(['reviewer.company', 'product', 'order', 'translations'])
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

    private function predefinedCountryMapData(): array
    {
        return CountryMapCatalog::all();
    }

    /**
     * @param  array<string, mixed>  $country
     * @return array<string, mixed>
     */
    private function withCountryFlag(array $country): array
    {
        $code = $country['country_code'] ?? null;

        if (! is_string($code) || strlen($code) !== 2) {
            return array_merge($country, [
                'flag' => null,
                'flag_icon' => null,
            ]);
        }

        $normalized = strtoupper($code);

        return array_merge($country, [
            'flag' => $this->countryCodeToEmoji($normalized),
            'flag_icon' => 'https://flagcdn.com/w40/'.strtolower($normalized).'.png',
        ]);
    }

    private function countryCodeToEmoji(string $countryCode): string
    {
        $emoji = '';

        foreach (str_split(strtoupper($countryCode)) as $char) {
            $emoji .= mb_chr(127397 + ord($char));
        }

        return $emoji;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $countries
     * @return array<int, array{group: string, countries: array<int, string>}>
     */
    private function groupByCountryCodePrefix(Collection $countries): array
    {
        return $countries
            ->filter(fn (array $country): bool => is_string($country['country_code'] ?? null))
            ->groupBy(fn (array $country): string => strtoupper(substr((string) $country['country_code'], 0, 1)))
            ->map(fn (Collection $items, string $prefix): array => [
                'group' => $prefix,
                'countries' => $items->pluck('country')->unique()->sort()->values()->all(),
            ])
            ->sortBy('group')
            ->values()
            ->all();
    }
}
