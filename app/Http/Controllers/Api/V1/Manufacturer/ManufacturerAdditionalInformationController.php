<?php

namespace App\Http\Controllers\Api\V1\Manufacturer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Manufacturer\IndexManufacturerAdditionalInformationRequest;
use App\Http\Requests\Api\V1\SubmitManufacturerAdditionalInformationRequest;
use App\Http\Resources\Api\V1\ManufacturerAdditionalInformationRequestResource;
use App\Services\Manufacturer\ManufacturerAdditionalInformationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ManufacturerAdditionalInformationController extends Controller
{
    public function __construct(
        private readonly ManufacturerAdditionalInformationService $service,
    ) {}

    public function index(IndexManufacturerAdditionalInformationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $records = $this->service->listForManufacturer(
            $request->user(),
            $validated['status'] ?? null,
        );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: ManufacturerAdditionalInformationRequestResource::collection($records),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function show(IndexManufacturerAdditionalInformationRequest $request, int $informationRequest): JsonResponse
    {
        $record = $this->service->findOwnedRequest($request->user(), $informationRequest);

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new ManufacturerAdditionalInformationRequestResource($record),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function submit(
        SubmitManufacturerAdditionalInformationRequest $httpRequest,
        int $informationRequest,
    ): JsonResponse {
        $submitted = $this->service->submitOwnedRequest(
            $httpRequest->user(),
            $informationRequest,
            $httpRequest->validated()['responses'],
        );

        return sendResponse(
            status: true,
            message: __('manufacturer_additional_information.submitted'),
            data: new ManufacturerAdditionalInformationRequestResource($submitted),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function pendingCount(IndexManufacturerAdditionalInformationRequest $request): JsonResponse
    {
        return sendResponse(
            status: true,
            message: __('common.success'),
            data: [
                'pending_count' => $this->service->countPendingForManufacturer($request->user()),
            ],
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}
