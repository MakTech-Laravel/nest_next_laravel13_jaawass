<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Filters\Api\V1\Admin\CertificateTypeFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexCertificateTypeRequest;
use App\Http\Requests\Api\V1\Admin\StoreCertificateTypeRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCertificateTypeRequest;
use App\Http\Resources\Api\V1\CertificateTypeResource;
use App\Services\CertificateTypeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class CertificateTypeController extends Controller
{

    public function __construct(private CertificateTypeService $certificateService) {}

    public function index(IndexCertificateTypeRequest $req)
    {
        
        $certificateTypes = CertificateTypeFilter::apply($this->certificateService->query(), $req)->paginate(
            perPage: $req->perPage(),
            pageName: 'page',
            page: $req->pageNumber(),
        );


        return  sendResponse(
            status: true,
            message: __('common.success'),
            data: CertificateTypeResource::collection($certificateTypes),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function store(StoreCertificateTypeRequest $req)
    {
        $validate = $req->validated();

        try {
            $certificateService = $this->certificateService->create($validate);
            return sendResponse(
                status: true,
                message: __('common.created'),
                data: new CertificateTypeResource($certificateService),
                statusCode: HttpStatus::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return sendResponse(
                status: true,
                message: __('common.error'),
                data: null,
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function show($id)
    {

        try {
            $certificateType = $this->certificateService->find($id);

            if (!$certificateType) {
                return sendResponse(
                    status: false,
                    message: __('common.not_found'),
                    data: null,
                    statusCode: HttpStatus::HTTP_NOT_FOUND
                );
            }

            return sendResponse(
                status: true,
                message: __('common.success'),
                data: new CertificateTypeResource($certificateType),
                statusCode: HttpStatus::HTTP_OK
            );
        } catch (\Exception $e) {
            return sendResponse(
                status: true,
                message: __('common.error'),
                data: null,
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function update(UpdateCertificateTypeRequest $req, $id)
    {

        $validated = $req->validated();
        try {


            $certificateType =  $this->certificateService->find($id);


            if (!$certificateType) {
                return sendResponse(
                    status: false,
                    message: __('common.not_found'),
                    data: null,
                    statusCode: HttpStatus::HTTP_NOT_FOUND
                );
            }

            $certificateType->update($validated);

            $certificateType = $certificateType->fresh();

            return sendResponse(
                status: true,
                message: __('common.success'),
                data: new CertificateTypeResource($certificateType),
                statusCode: HttpStatus::HTTP_OK
            );
        } catch (\Exception $e) {
            return sendResponse(
                status: true,
                message: __('common.error'),
                data: null,
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function destroy($id)
    {

        try {
            $certificateType = $this->certificateService->find($id);

            if (!$certificateType) {
                return sendResponse(
                    status: false,
                    message: __('common.not_found'),
                    data: null,
                    statusCode: HttpStatus::HTTP_NOT_FOUND
                );
            }

            $this->certificateService->delete($id);

            return sendResponse(
                status: true,
                message: __('common.deleted'),
                data: null,
                statusCode: HttpStatus::HTTP_OK
            );
        } catch (\Exception $e) {
            return sendResponse(
                status: true,
                message: __('common.error'),
                data: null,
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
