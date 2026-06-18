<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreManufacturerAdditionalInformationRequest;
use App\Http\Resources\Api\V1\ManufacturerAdditionalInformationRequestResource;
use App\Models\ManufacturerAdditionalInformationRequest;
use App\Models\User;
use App\Services\Manufacturer\ManufacturerAdditionalInformationService;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ManufacturerAdditionalInformationController extends Controller
{
    public function __construct(
        private readonly ManufacturerAdditionalInformationService $service,
    ) {}

    public function index(int $manufacturer)
    {
        $user = User::query()
            ->where('id', $manufacturer)
            ->where('role', 'manufacturer')
            ->first();

        if ($user === null) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: [],
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        $requests = ManufacturerAdditionalInformationRequest::query()
            ->with(['requestedBy', 'responses'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

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
    ) {
        $manufacturerUser = User::query()
            ->where('id', $manufacturer)
            ->where('role', 'manufacturer')
            ->with('company')
            ->first();

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

    public function show(int $informationRequest)
    {
        $record = ManufacturerAdditionalInformationRequest::query()
            ->with(['requestedBy', 'responses', 'manufacturer.company'])
            ->find($informationRequest);

        if ($record === null) {
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
}
