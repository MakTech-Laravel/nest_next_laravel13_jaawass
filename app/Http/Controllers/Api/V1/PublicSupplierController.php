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
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class PublicSupplierController extends Controller
{
    public function __construct(
        private readonly PublicSupplierCatalogService $supplierCatalogService,
        private readonly ProductCatalogService $productCatalogService,
        private readonly EventTrackerService $eventTracker,
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
            ->map(function (array $country) use ($countryCounts): array {
                $key = strtolower(trim($country['country']));
                $matched = $countryCounts->get($key);

                return $this->withCountryFlag([
                    'name' => $country['name'],
                    'country' => $country['country'],
                    'country_code' => $country['country_code'],
                    'group' => $country['group'],
                    'subregion' => $country['subregion'] ?? null,
                    'coordinates' => $country['coordinates'] ?? ['lat' => null, 'lng' => null],
                    'suppliers_count' => (int) ($matched['suppliers_count'] ?? 0),
                    'has_suppliers' => isset($matched['suppliers_count']) && (int) $matched['suppliers_count'] > 0,
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
                'has_suppliers' => $entry['suppliers_count'] > 0,
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

    /**
     * @return array<int, array{name: string, country: string, country_code: string, group: string, subregion: string}>
     */
    private function predefinedCountryMapData(): array
    {
        return [
            // Africa
            ['name' => 'Algeria', 'country' => 'Algeria', 'country_code' => 'DZ', 'group' => 'Africa', 'subregion' => 'Northern Africa'],
            ['name' => 'Angola', 'country' => 'Angola', 'country_code' => 'AO', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Benin', 'country' => 'Benin', 'country_code' => 'BJ', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Botswana', 'country' => 'Botswana', 'country_code' => 'BW', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Burkina Faso', 'country' => 'Burkina Faso', 'country_code' => 'BF', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Burundi', 'country' => 'Burundi', 'country_code' => 'BI', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Cabo Verde', 'country' => 'Cabo Verde', 'country_code' => 'CV', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Cameroon', 'country' => 'Cameroon', 'country_code' => 'CM', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Central African Republic', 'country' => 'Central African Republic', 'country_code' => 'CF', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Chad', 'country' => 'Chad', 'country_code' => 'TD', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Comoros', 'country' => 'Comoros', 'country_code' => 'KM', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Congo', 'country' => 'Congo', 'country_code' => 'CG', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Congo (Democratic Republic)', 'country' => 'Congo (Democratic Republic)', 'country_code' => 'CD', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => "Cote d'Ivoire", 'country' => "Cote d'Ivoire", 'country_code' => 'CI', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Djibouti', 'country' => 'Djibouti', 'country_code' => 'DJ', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Egypt', 'country' => 'Egypt', 'country_code' => 'EG', 'group' => 'Africa', 'subregion' => 'Northern Africa'],
            ['name' => 'Equatorial Guinea', 'country' => 'Equatorial Guinea', 'country_code' => 'GQ', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Eritrea', 'country' => 'Eritrea', 'country_code' => 'ER', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Eswatini', 'country' => 'Eswatini', 'country_code' => 'SZ', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Ethiopia', 'country' => 'Ethiopia', 'country_code' => 'ET', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Gabon', 'country' => 'Gabon', 'country_code' => 'GA', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Gambia', 'country' => 'Gambia', 'country_code' => 'GM', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Ghana', 'country' => 'Ghana', 'country_code' => 'GH', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Guinea', 'country' => 'Guinea', 'country_code' => 'GN', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Guinea-Bissau', 'country' => 'Guinea-Bissau', 'country_code' => 'GW', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Kenya', 'country' => 'Kenya', 'country_code' => 'KE', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Lesotho', 'country' => 'Lesotho', 'country_code' => 'LS', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Liberia', 'country' => 'Liberia', 'country_code' => 'LR', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Libya', 'country' => 'Libya', 'country_code' => 'LY', 'group' => 'Africa', 'subregion' => 'Northern Africa'],
            ['name' => 'Madagascar', 'country' => 'Madagascar', 'country_code' => 'MG', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Malawi', 'country' => 'Malawi', 'country_code' => 'MW', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Mali', 'country' => 'Mali', 'country_code' => 'ML', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Mauritania', 'country' => 'Mauritania', 'country_code' => 'MR', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Mauritius', 'country' => 'Mauritius', 'country_code' => 'MU', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Morocco', 'country' => 'Morocco', 'country_code' => 'MA', 'group' => 'Africa', 'subregion' => 'Northern Africa'],
            ['name' => 'Mozambique', 'country' => 'Mozambique', 'country_code' => 'MZ', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Namibia', 'country' => 'Namibia', 'country_code' => 'NA', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Niger', 'country' => 'Niger', 'country_code' => 'NE', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Nigeria', 'country' => 'Nigeria', 'country_code' => 'NG', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Rwanda', 'country' => 'Rwanda', 'country_code' => 'RW', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Sao Tome and Principe', 'country' => 'Sao Tome and Principe', 'country_code' => 'ST', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Senegal', 'country' => 'Senegal', 'country_code' => 'SN', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Seychelles', 'country' => 'Seychelles', 'country_code' => 'SC', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Sierra Leone', 'country' => 'Sierra Leone', 'country_code' => 'SL', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Somalia', 'country' => 'Somalia', 'country_code' => 'SO', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'South Africa', 'country' => 'South Africa', 'country_code' => 'ZA', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'South Sudan', 'country' => 'South Sudan', 'country_code' => 'SS', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Sudan', 'country' => 'Sudan', 'country_code' => 'SD', 'group' => 'Africa', 'subregion' => 'Northern Africa'],
            ['name' => 'Tanzania', 'country' => 'Tanzania', 'country_code' => 'TZ', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Togo', 'country' => 'Togo', 'country_code' => 'TG', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Tunisia', 'country' => 'Tunisia', 'country_code' => 'TN', 'group' => 'Africa', 'subregion' => 'Northern Africa'],
            ['name' => 'Uganda', 'country' => 'Uganda', 'country_code' => 'UG', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Zambia', 'country' => 'Zambia', 'country_code' => 'ZM', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            ['name' => 'Zimbabwe', 'country' => 'Zimbabwe', 'country_code' => 'ZW', 'group' => 'Africa', 'subregion' => 'Sub-Saharan Africa'],
            // Americas
            ['name' => 'Antigua and Barbuda', 'country' => 'Antigua and Barbuda', 'country_code' => 'AG', 'group' => 'Americas', 'subregion' => 'Caribbean'],
            ['name' => 'Argentina', 'country' => 'Argentina', 'country_code' => 'AR', 'group' => 'Americas', 'subregion' => 'South America'],
            ['name' => 'Bahamas', 'country' => 'Bahamas', 'country_code' => 'BS', 'group' => 'Americas', 'subregion' => 'Caribbean'],
            ['name' => 'Barbados', 'country' => 'Barbados', 'country_code' => 'BB', 'group' => 'Americas', 'subregion' => 'Caribbean'],
            ['name' => 'Belize', 'country' => 'Belize', 'country_code' => 'BZ', 'group' => 'Americas', 'subregion' => 'Central America'],
            ['name' => 'Bolivia', 'country' => 'Bolivia', 'country_code' => 'BO', 'group' => 'Americas', 'subregion' => 'South America'],
            ['name' => 'Brazil', 'country' => 'Brazil', 'country_code' => 'BR', 'group' => 'Americas', 'subregion' => 'South America'],
            ['name' => 'Canada', 'country' => 'Canada', 'country_code' => 'CA', 'group' => 'Americas', 'subregion' => 'North America'],
            ['name' => 'Chile', 'country' => 'Chile', 'country_code' => 'CL', 'group' => 'Americas', 'subregion' => 'South America'],
            ['name' => 'Colombia', 'country' => 'Colombia', 'country_code' => 'CO', 'group' => 'Americas', 'subregion' => 'South America'],
            ['name' => 'Costa Rica', 'country' => 'Costa Rica', 'country_code' => 'CR', 'group' => 'Americas', 'subregion' => 'Central America'],
            ['name' => 'Cuba', 'country' => 'Cuba', 'country_code' => 'CU', 'group' => 'Americas', 'subregion' => 'Caribbean'],
            ['name' => 'Dominica', 'country' => 'Dominica', 'country_code' => 'DM', 'group' => 'Americas', 'subregion' => 'Caribbean'],
            ['name' => 'Dominican Republic', 'country' => 'Dominican Republic', 'country_code' => 'DO', 'group' => 'Americas', 'subregion' => 'Caribbean'],
            ['name' => 'Ecuador', 'country' => 'Ecuador', 'country_code' => 'EC', 'group' => 'Americas', 'subregion' => 'South America'],
            ['name' => 'El Salvador', 'country' => 'El Salvador', 'country_code' => 'SV', 'group' => 'Americas', 'subregion' => 'Central America'],
            ['name' => 'Grenada', 'country' => 'Grenada', 'country_code' => 'GD', 'group' => 'Americas', 'subregion' => 'Caribbean'],
            ['name' => 'Guatemala', 'country' => 'Guatemala', 'country_code' => 'GT', 'group' => 'Americas', 'subregion' => 'Central America'],
            ['name' => 'Guyana', 'country' => 'Guyana', 'country_code' => 'GY', 'group' => 'Americas', 'subregion' => 'South America'],
            ['name' => 'Haiti', 'country' => 'Haiti', 'country_code' => 'HT', 'group' => 'Americas', 'subregion' => 'Caribbean'],
            ['name' => 'Honduras', 'country' => 'Honduras', 'country_code' => 'HN', 'group' => 'Americas', 'subregion' => 'Central America'],
            ['name' => 'Jamaica', 'country' => 'Jamaica', 'country_code' => 'JM', 'group' => 'Americas', 'subregion' => 'Caribbean'],
            ['name' => 'Mexico', 'country' => 'Mexico', 'country_code' => 'MX', 'group' => 'Americas', 'subregion' => 'North America'],
            ['name' => 'Nicaragua', 'country' => 'Nicaragua', 'country_code' => 'NI', 'group' => 'Americas', 'subregion' => 'Central America'],
            ['name' => 'Panama', 'country' => 'Panama', 'country_code' => 'PA', 'group' => 'Americas', 'subregion' => 'Central America'],
            ['name' => 'Paraguay', 'country' => 'Paraguay', 'country_code' => 'PY', 'group' => 'Americas', 'subregion' => 'South America'],
            ['name' => 'Peru', 'country' => 'Peru', 'country_code' => 'PE', 'group' => 'Americas', 'subregion' => 'South America'],
            ['name' => 'Saint Kitts and Nevis', 'country' => 'Saint Kitts and Nevis', 'country_code' => 'KN', 'group' => 'Americas', 'subregion' => 'Caribbean'],
            ['name' => 'Saint Lucia', 'country' => 'Saint Lucia', 'country_code' => 'LC', 'group' => 'Americas', 'subregion' => 'Caribbean'],
            ['name' => 'Saint Vincent and the Grenadines', 'country' => 'Saint Vincent and the Grenadines', 'country_code' => 'VC', 'group' => 'Americas', 'subregion' => 'Caribbean'],
            ['name' => 'Suriname', 'country' => 'Suriname', 'country_code' => 'SR', 'group' => 'Americas', 'subregion' => 'South America'],
            ['name' => 'Trinidad and Tobago', 'country' => 'Trinidad and Tobago', 'country_code' => 'TT', 'group' => 'Americas', 'subregion' => 'Caribbean'],
            ['name' => 'United States', 'country' => 'United States', 'country_code' => 'US', 'group' => 'Americas', 'subregion' => 'North America'],
            ['name' => 'Uruguay', 'country' => 'Uruguay', 'country_code' => 'UY', 'group' => 'Americas', 'subregion' => 'South America'],
            ['name' => 'Venezuela', 'country' => 'Venezuela', 'country_code' => 'VE', 'group' => 'Americas', 'subregion' => 'South America'],
            // Asia
            ['name' => 'Afghanistan', 'country' => 'Afghanistan', 'country_code' => 'AF', 'group' => 'Asia', 'subregion' => 'Southern Asia'],
            ['name' => 'Armenia', 'country' => 'Armenia', 'country_code' => 'AM', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            ['name' => 'Azerbaijan', 'country' => 'Azerbaijan', 'country_code' => 'AZ', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            ['name' => 'Bahrain', 'country' => 'Bahrain', 'country_code' => 'BH', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            ['name' => 'Bangladesh', 'country' => 'Bangladesh', 'country_code' => 'BD', 'group' => 'Asia', 'subregion' => 'Southern Asia'],
            ['name' => 'Bhutan', 'country' => 'Bhutan', 'country_code' => 'BT', 'group' => 'Asia', 'subregion' => 'Southern Asia'],
            ['name' => 'Brunei', 'country' => 'Brunei', 'country_code' => 'BN', 'group' => 'Asia', 'subregion' => 'South-Eastern Asia'],
            ['name' => 'Cambodia', 'country' => 'Cambodia', 'country_code' => 'KH', 'group' => 'Asia', 'subregion' => 'South-Eastern Asia'],
            ['name' => 'China', 'country' => 'China', 'country_code' => 'CN', 'group' => 'Asia', 'subregion' => 'Eastern Asia'],
            ['name' => 'Cyprus', 'country' => 'Cyprus', 'country_code' => 'CY', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            ['name' => 'Georgia', 'country' => 'Georgia', 'country_code' => 'GE', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            ['name' => 'India', 'country' => 'India', 'country_code' => 'IN', 'group' => 'Asia', 'subregion' => 'Southern Asia'],
            ['name' => 'Indonesia', 'country' => 'Indonesia', 'country_code' => 'ID', 'group' => 'Asia', 'subregion' => 'South-Eastern Asia'],
            ['name' => 'Iran', 'country' => 'Iran', 'country_code' => 'IR', 'group' => 'Asia', 'subregion' => 'Southern Asia'],
            ['name' => 'Iraq', 'country' => 'Iraq', 'country_code' => 'IQ', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            ['name' => 'Israel', 'country' => 'Israel', 'country_code' => 'IL', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            ['name' => 'Japan', 'country' => 'Japan', 'country_code' => 'JP', 'group' => 'Asia', 'subregion' => 'Eastern Asia'],
            ['name' => 'Jordan', 'country' => 'Jordan', 'country_code' => 'JO', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            ['name' => 'Kazakhstan', 'country' => 'Kazakhstan', 'country_code' => 'KZ', 'group' => 'Asia', 'subregion' => 'Central Asia'],
            ['name' => 'Kuwait', 'country' => 'Kuwait', 'country_code' => 'KW', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            ['name' => 'Kyrgyzstan', 'country' => 'Kyrgyzstan', 'country_code' => 'KG', 'group' => 'Asia', 'subregion' => 'Central Asia'],
            ['name' => 'Laos', 'country' => 'Laos', 'country_code' => 'LA', 'group' => 'Asia', 'subregion' => 'South-Eastern Asia'],
            ['name' => 'Lebanon', 'country' => 'Lebanon', 'country_code' => 'LB', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            ['name' => 'Malaysia', 'country' => 'Malaysia', 'country_code' => 'MY', 'group' => 'Asia', 'subregion' => 'South-Eastern Asia'],
            ['name' => 'Maldives', 'country' => 'Maldives', 'country_code' => 'MV', 'group' => 'Asia', 'subregion' => 'Southern Asia'],
            ['name' => 'Mongolia', 'country' => 'Mongolia', 'country_code' => 'MN', 'group' => 'Asia', 'subregion' => 'Eastern Asia'],
            ['name' => 'Myanmar', 'country' => 'Myanmar', 'country_code' => 'MM', 'group' => 'Asia', 'subregion' => 'South-Eastern Asia'],
            ['name' => 'Nepal', 'country' => 'Nepal', 'country_code' => 'NP', 'group' => 'Asia', 'subregion' => 'Southern Asia'],
            ['name' => 'North Korea', 'country' => 'North Korea', 'country_code' => 'KP', 'group' => 'Asia', 'subregion' => 'Eastern Asia'],
            ['name' => 'Oman', 'country' => 'Oman', 'country_code' => 'OM', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            ['name' => 'Pakistan', 'country' => 'Pakistan', 'country_code' => 'PK', 'group' => 'Asia', 'subregion' => 'Southern Asia'],
            ['name' => 'Palestine', 'country' => 'Palestine', 'country_code' => 'PS', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            ['name' => 'Philippines', 'country' => 'Philippines', 'country_code' => 'PH', 'group' => 'Asia', 'subregion' => 'South-Eastern Asia'],
            ['name' => 'Qatar', 'country' => 'Qatar', 'country_code' => 'QA', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            ['name' => 'Saudi Arabia', 'country' => 'Saudi Arabia', 'country_code' => 'SA', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            ['name' => 'Singapore', 'country' => 'Singapore', 'country_code' => 'SG', 'group' => 'Asia', 'subregion' => 'South-Eastern Asia'],
            ['name' => 'South Korea', 'country' => 'South Korea', 'country_code' => 'KR', 'group' => 'Asia', 'subregion' => 'Eastern Asia'],
            ['name' => 'Sri Lanka', 'country' => 'Sri Lanka', 'country_code' => 'LK', 'group' => 'Asia', 'subregion' => 'Southern Asia'],
            ['name' => 'Syria', 'country' => 'Syria', 'country_code' => 'SY', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            ['name' => 'Taiwan', 'country' => 'Taiwan', 'country_code' => 'TW', 'group' => 'Asia', 'subregion' => 'Eastern Asia'],
            ['name' => 'Tajikistan', 'country' => 'Tajikistan', 'country_code' => 'TJ', 'group' => 'Asia', 'subregion' => 'Central Asia'],
            ['name' => 'Thailand', 'country' => 'Thailand', 'country_code' => 'TH', 'group' => 'Asia', 'subregion' => 'South-Eastern Asia'],
            ['name' => 'Timor-Leste', 'country' => 'Timor-Leste', 'country_code' => 'TL', 'group' => 'Asia', 'subregion' => 'South-Eastern Asia'],
            ['name' => 'Turkey', 'country' => 'Turkey', 'country_code' => 'TR', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            ['name' => 'Turkmenistan', 'country' => 'Turkmenistan', 'country_code' => 'TM', 'group' => 'Asia', 'subregion' => 'Central Asia'],
            ['name' => 'United Arab Emirates', 'country' => 'United Arab Emirates', 'country_code' => 'AE', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            ['name' => 'Uzbekistan', 'country' => 'Uzbekistan', 'country_code' => 'UZ', 'group' => 'Asia', 'subregion' => 'Central Asia'],
            ['name' => 'Vietnam', 'country' => 'Vietnam', 'country_code' => 'VN', 'group' => 'Asia', 'subregion' => 'South-Eastern Asia'],
            ['name' => 'Yemen', 'country' => 'Yemen', 'country_code' => 'YE', 'group' => 'Asia', 'subregion' => 'Western Asia'],
            // Europe
            ['name' => 'Albania', 'country' => 'Albania', 'country_code' => 'AL', 'group' => 'Europe', 'subregion' => 'Southern Europe'],
            ['name' => 'Andorra', 'country' => 'Andorra', 'country_code' => 'AD', 'group' => 'Europe', 'subregion' => 'Southern Europe'],
            ['name' => 'Austria', 'country' => 'Austria', 'country_code' => 'AT', 'group' => 'Europe', 'subregion' => 'Western Europe'],
            ['name' => 'Belarus', 'country' => 'Belarus', 'country_code' => 'BY', 'group' => 'Europe', 'subregion' => 'Eastern Europe'],
            ['name' => 'Belgium', 'country' => 'Belgium', 'country_code' => 'BE', 'group' => 'Europe', 'subregion' => 'Western Europe'],
            ['name' => 'Bosnia and Herzegovina', 'country' => 'Bosnia and Herzegovina', 'country_code' => 'BA', 'group' => 'Europe', 'subregion' => 'Southern Europe'],
            ['name' => 'Bulgaria', 'country' => 'Bulgaria', 'country_code' => 'BG', 'group' => 'Europe', 'subregion' => 'Eastern Europe'],
            ['name' => 'Croatia', 'country' => 'Croatia', 'country_code' => 'HR', 'group' => 'Europe', 'subregion' => 'Southern Europe'],
            ['name' => 'Czech Republic', 'country' => 'Czech Republic', 'country_code' => 'CZ', 'group' => 'Europe', 'subregion' => 'Eastern Europe'],
            ['name' => 'Denmark', 'country' => 'Denmark', 'country_code' => 'DK', 'group' => 'Europe', 'subregion' => 'Northern Europe'],
            ['name' => 'Estonia', 'country' => 'Estonia', 'country_code' => 'EE', 'group' => 'Europe', 'subregion' => 'Northern Europe'],
            ['name' => 'Finland', 'country' => 'Finland', 'country_code' => 'FI', 'group' => 'Europe', 'subregion' => 'Northern Europe'],
            ['name' => 'France', 'country' => 'France', 'country_code' => 'FR', 'group' => 'Europe', 'subregion' => 'Western Europe'],
            ['name' => 'Germany', 'country' => 'Germany', 'country_code' => 'DE', 'group' => 'Europe', 'subregion' => 'Western Europe'],
            ['name' => 'Greece', 'country' => 'Greece', 'country_code' => 'GR', 'group' => 'Europe', 'subregion' => 'Southern Europe'],
            ['name' => 'Hungary', 'country' => 'Hungary', 'country_code' => 'HU', 'group' => 'Europe', 'subregion' => 'Eastern Europe'],
            ['name' => 'Iceland', 'country' => 'Iceland', 'country_code' => 'IS', 'group' => 'Europe', 'subregion' => 'Northern Europe'],
            ['name' => 'Ireland', 'country' => 'Ireland', 'country_code' => 'IE', 'group' => 'Europe', 'subregion' => 'Northern Europe'],
            ['name' => 'Italy', 'country' => 'Italy', 'country_code' => 'IT', 'group' => 'Europe', 'subregion' => 'Southern Europe'],
            ['name' => 'Kosovo', 'country' => 'Kosovo', 'country_code' => 'XK', 'group' => 'Europe', 'subregion' => 'Southern Europe'],
            ['name' => 'Latvia', 'country' => 'Latvia', 'country_code' => 'LV', 'group' => 'Europe', 'subregion' => 'Northern Europe'],
            ['name' => 'Liechtenstein', 'country' => 'Liechtenstein', 'country_code' => 'LI', 'group' => 'Europe', 'subregion' => 'Western Europe'],
            ['name' => 'Lithuania', 'country' => 'Lithuania', 'country_code' => 'LT', 'group' => 'Europe', 'subregion' => 'Northern Europe'],
            ['name' => 'Luxembourg', 'country' => 'Luxembourg', 'country_code' => 'LU', 'group' => 'Europe', 'subregion' => 'Western Europe'],
            ['name' => 'Malta', 'country' => 'Malta', 'country_code' => 'MT', 'group' => 'Europe', 'subregion' => 'Southern Europe'],
            ['name' => 'Moldova', 'country' => 'Moldova', 'country_code' => 'MD', 'group' => 'Europe', 'subregion' => 'Eastern Europe'],
            ['name' => 'Monaco', 'country' => 'Monaco', 'country_code' => 'MC', 'group' => 'Europe', 'subregion' => 'Western Europe'],
            ['name' => 'Montenegro', 'country' => 'Montenegro', 'country_code' => 'ME', 'group' => 'Europe', 'subregion' => 'Southern Europe'],
            ['name' => 'Netherlands', 'country' => 'Netherlands', 'country_code' => 'NL', 'group' => 'Europe', 'subregion' => 'Western Europe'],
            ['name' => 'North Macedonia', 'country' => 'North Macedonia', 'country_code' => 'MK', 'group' => 'Europe', 'subregion' => 'Southern Europe'],
            ['name' => 'Norway', 'country' => 'Norway', 'country_code' => 'NO', 'group' => 'Europe', 'subregion' => 'Northern Europe'],
            ['name' => 'Poland', 'country' => 'Poland', 'country_code' => 'PL', 'group' => 'Europe', 'subregion' => 'Eastern Europe'],
            ['name' => 'Portugal', 'country' => 'Portugal', 'country_code' => 'PT', 'group' => 'Europe', 'subregion' => 'Southern Europe'],
            ['name' => 'Romania', 'country' => 'Romania', 'country_code' => 'RO', 'group' => 'Europe', 'subregion' => 'Eastern Europe'],
            ['name' => 'Russia', 'country' => 'Russia', 'country_code' => 'RU', 'group' => 'Europe', 'subregion' => 'Eastern Europe'],
            ['name' => 'San Marino', 'country' => 'San Marino', 'country_code' => 'SM', 'group' => 'Europe', 'subregion' => 'Southern Europe'],
            ['name' => 'Serbia', 'country' => 'Serbia', 'country_code' => 'RS', 'group' => 'Europe', 'subregion' => 'Southern Europe'],
            ['name' => 'Slovakia', 'country' => 'Slovakia', 'country_code' => 'SK', 'group' => 'Europe', 'subregion' => 'Eastern Europe'],
            ['name' => 'Slovenia', 'country' => 'Slovenia', 'country_code' => 'SI', 'group' => 'Europe', 'subregion' => 'Southern Europe'],
            ['name' => 'Spain', 'country' => 'Spain', 'country_code' => 'ES', 'group' => 'Europe', 'subregion' => 'Southern Europe'],
            ['name' => 'Sweden', 'country' => 'Sweden', 'country_code' => 'SE', 'group' => 'Europe', 'subregion' => 'Northern Europe'],
            ['name' => 'Switzerland', 'country' => 'Switzerland', 'country_code' => 'CH', 'group' => 'Europe', 'subregion' => 'Western Europe'],
            ['name' => 'Ukraine', 'country' => 'Ukraine', 'country_code' => 'UA', 'group' => 'Europe', 'subregion' => 'Eastern Europe'],
            ['name' => 'United Kingdom', 'country' => 'United Kingdom', 'country_code' => 'GB', 'group' => 'Europe', 'subregion' => 'Northern Europe'],
            ['name' => 'Vatican City', 'country' => 'Vatican City', 'country_code' => 'VA', 'group' => 'Europe', 'subregion' => 'Southern Europe'],
            // Oceania
            ['name' => 'Australia', 'country' => 'Australia', 'country_code' => 'AU', 'group' => 'Oceania', 'subregion' => 'Australasia'],
            ['name' => 'Fiji', 'country' => 'Fiji', 'country_code' => 'FJ', 'group' => 'Oceania', 'subregion' => 'Melanesia'],
            ['name' => 'Kiribati', 'country' => 'Kiribati', 'country_code' => 'KI', 'group' => 'Oceania', 'subregion' => 'Micronesia'],
            ['name' => 'Marshall Islands', 'country' => 'Marshall Islands', 'country_code' => 'MH', 'group' => 'Oceania', 'subregion' => 'Micronesia'],
            ['name' => 'Micronesia', 'country' => 'Micronesia', 'country_code' => 'FM', 'group' => 'Oceania', 'subregion' => 'Micronesia'],
            ['name' => 'Nauru', 'country' => 'Nauru', 'country_code' => 'NR', 'group' => 'Oceania', 'subregion' => 'Micronesia'],
            ['name' => 'New Zealand', 'country' => 'New Zealand', 'country_code' => 'NZ', 'group' => 'Oceania', 'subregion' => 'Australasia'],
            ['name' => 'Palau', 'country' => 'Palau', 'country_code' => 'PW', 'group' => 'Oceania', 'subregion' => 'Micronesia'],
            ['name' => 'Papua New Guinea', 'country' => 'Papua New Guinea', 'country_code' => 'PG', 'group' => 'Oceania', 'subregion' => 'Melanesia'],
            ['name' => 'Samoa', 'country' => 'Samoa', 'country_code' => 'WS', 'group' => 'Oceania', 'subregion' => 'Polynesia'],
            ['name' => 'Solomon Islands', 'country' => 'Solomon Islands', 'country_code' => 'SB', 'group' => 'Oceania', 'subregion' => 'Melanesia'],
            ['name' => 'Tonga', 'country' => 'Tonga', 'country_code' => 'TO', 'group' => 'Oceania', 'subregion' => 'Polynesia'],
            ['name' => 'Tuvalu', 'country' => 'Tuvalu', 'country_code' => 'TV', 'group' => 'Oceania', 'subregion' => 'Polynesia'],
            ['name' => 'Vanuatu', 'country' => 'Vanuatu', 'country_code' => 'VU', 'group' => 'Oceania', 'subregion' => 'Melanesia'],
        ];
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
