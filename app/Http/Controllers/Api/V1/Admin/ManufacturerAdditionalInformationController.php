<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexManufacturerAdditionalInformationRequest;
use App\Http\Requests\Api\V1\Admin\StoreManufacturerAdditionalInformationRequest;
use App\Http\Resources\Api\V1\ManufacturerAdditionalInformationRequestResource;
use App\Models\User;
use App\Services\Manufacturer\ManufacturerAdditionalInformationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ManufacturerAdditionalInformationController extends Controller
{
    public function __construct(
        private readonly ManufacturerAdditionalInformationService $service,
    ) {}

    public function globalIndex(IndexManufacturerAdditionalInformationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $records = $this->service->paginateForAdmin(
            $validated['status'] ?? null,
            (int) ($validated['per_page'] ?? 10),
        );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: ManufacturerAdditionalInformationRequestResource::collection($records),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function index(int $manufacturer): JsonResponse
    {
        $user = $this->findManufacturerOrNotFound($manufacturer);

        if ($user === null) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: [],
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        $requests = $this->service->listForManufacturerAdmin($user);

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: ManufacturerAdditionalInformationRequestResource::collection($requests),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function store(
        StoreManufacturerAdditionalInformationRequest $request,
        int $manufacturer,
    ): JsonResponse {
        $manufacturerUser = $this->findManufacturerOrNotFound($manufacturer, loadCompany: true);

        if ($manufacturerUser === null) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: [],
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        $validated = $request->validated();

        $informationRequest = $this->service->createRequest(
            manufacturer: $manufacturerUser,
            admin: $request->user(),
            message: $validated['message'],
            allowedTypes: $validated['allowed_types'],
        );

        return sendResponse(
            status: true,
            message: __('manufacturer_additional_information.request_sent'),
            data: new ManufacturerAdditionalInformationRequestResource($informationRequest),
            statusCode: HttpStatus::HTTP_CREATED,
        );
    }

    public function show(int $informationRequest): JsonResponse
    {
        try {
            $record = $this->service->findForAdmin($informationRequest);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: [],
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new ManufacturerAdditionalInformationRequestResource($record),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    private function findManufacturerOrNotFound(int $manufacturerId, bool $loadCompany = false): ?User
    {
        $query = User::query()
            ->where('id', $manufacturerId)
            ->where('role', 'manufacturer');

        if ($loadCompany) {
            $query->with('company');
        }

        return $query->first();
    }
}
