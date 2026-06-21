<?php

namespace App\Http\Controllers\Api\V1\Manufacturer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Manufacturer\Markets\ManufacturerExportMarketCountriesRequest;
use App\Http\Requests\Api\V1\Manufacturer\Markets\StoreManufacturerExportMarketRegionRequest;
use App\Http\Requests\Api\V1\Manufacturer\Markets\SyncManufacturerExportMarketCountriesRequest;
use App\Http\Requests\Api\V1\Manufacturer\Markets\UpdateManufacturerExportMarketRegionRequest;
use App\Models\ManufacturerExportMarket;
use App\Services\Manufacturer\ManufacturerExportMarketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ManufacturerExportMarketsController extends Controller
{
    public function __construct(
        private readonly ManufacturerExportMarketService $exportMarketService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return sendResponse(
            status: true,
            message: __('api.manufacturer_export_markets_fetched_successfully'),
            data: $this->exportMarketService->overview($request->user()),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function countries(ManufacturerExportMarketCountriesRequest $request): JsonResponse
    {
        $paginator = $this->exportMarketService->countries(
            manufacturer: $request->user(),
            search: $request->searchTerm(),
            geographicRegion: $request->geographicRegionFilter(),
            perPage: $request->perPage(),
            page: $request->pageNumber(),
        );

        return sendResponse(
            status: true,
            message: __('api.manufacturer_export_market_countries_fetched_successfully'),
            data: JsonResource::collection($paginator),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function storeRegion(StoreManufacturerExportMarketRegionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return sendResponse(
            status: true,
            message: __('api.manufacturer_export_market_region_created_successfully'),
            data: $this->exportMarketService->storeRegion(
                $request->user(),
                $validated['region'],
                $validated['country_codes'],
            ),
            statusCode: HttpStatus::HTTP_CREATED,
        );
    }

    public function updateRegion(
        UpdateManufacturerExportMarketRegionRequest $request,
        ManufacturerExportMarket $market,
    ): JsonResponse {
        return sendResponse(
            status: true,
            message: __('api.manufacturer_export_market_region_updated_successfully'),
            data: $this->exportMarketService->updateRegion(
                $request->user(),
                $market,
                $request->validated('country_codes'),
            ),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function destroyRegion(Request $request, ManufacturerExportMarket $market): JsonResponse
    {
        $this->exportMarketService->destroyRegion($request->user(), $market);

        return sendResponse(
            status: true,
            message: __('api.manufacturer_export_market_region_deleted_successfully'),
            data: null,
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function syncCountries(SyncManufacturerExportMarketCountriesRequest $request): JsonResponse
    {
        return sendResponse(
            status: true,
            message: __('api.manufacturer_export_market_countries_synced_successfully'),
            data: $this->exportMarketService->syncCountries(
                $request->user(),
                $request->validated('country_codes'),
            ),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}
