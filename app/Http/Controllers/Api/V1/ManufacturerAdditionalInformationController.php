<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SubmitManufacturerAdditionalInformationRequest;
use App\Http\Resources\Api\V1\ManufacturerAdditionalInformationRequestResource;
use App\Services\Manufacturer\ManufacturerAdditionalInformationService;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ManufacturerAdditionalInformationController extends Controller
{
    public function __construct(
        private readonly ManufacturerAdditionalInformationService $service,
    ) {}

    public function show(string $token)
    {
        $request = $this->service->findByToken($token);

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new ManufacturerAdditionalInformationRequestResource($request),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function submit(SubmitManufacturerAdditionalInformationRequest $httpRequest, string $token)
    {
        $informationRequest = $this->service->findSubmittableByToken($token);

        $validated = $httpRequest->validated();

        $submitted = $this->service->submitResponses(
            $informationRequest,
            $validated['responses'],
        );

        return sendResponse(
            status: true,
            message: __('manufacturer_additional_information.submitted'),
            data: new ManufacturerAdditionalInformationRequestResource($submitted),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}
