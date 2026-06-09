<?php

namespace App\Http\Controllers\Api\V1\Manufacturer;

use App\Enums\CertificateStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Manufacturer\IndexCertificateRequest;
use App\Http\Requests\Api\V1\Manufacturer\StoreCertificateRequest;
use App\Http\Requests\Api\V1\Manufacturer\UpdateCertificateRequest;
use App\Http\Resources\Api\V1\Manufacturer\CertificateResourece;
use App\Filters\Api\V1\Manufacturer\CertificateFilter;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class CertificationController extends Controller
{
    public function __construct(private CertificateService $certificateService) {}


    public function stats(Request $req){

        $expired = $this->certificateService->query()
        ->where('user_id', $req->user()->id)
        ->where('expiry_date', '<', Carbon::now())
        ->count();

        $valid = $this->certificateService->query()
        ->where('user_id', $req->user()->id)
        ->where('expiry_date', '>', Carbon::now())
        ->count();
        
        return sendResponse(
            status: true,
            message: __('common.success'),
            data: [
                'expired' => $expired,
                'valid' => $valid,
            ],
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function index(IndexCertificateRequest $req)
    {

        $certificates = CertificateFilter::apply($this->certificateService->query(), $req)->paginate(
            perPage: $req->perPage(),
            pageName: 'page',
            page: $req->pageNumber(),
        );


        return  sendResponse(
            status: true,
            message: __('common.success'),
            data: CertificateResourece::collection($certificates),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function show($id)
    {

        try {
            $certificate = $this->certificateService->find($id);

            if (!$certificate) {
                return sendResponse(
                    status: false,
                    message: __('common.not_found'),
                    data: null,
                    statusCode: HttpStatus::HTTP_NOT_FOUND
                );
            }


            $certificate->load('certificateType');
            return sendResponse(
                status: true,
                message: __('common.success'),
                data: new CertificateResourece($certificate),
                statusCode: HttpStatus::HTTP_OK
            );
        } catch (\Exception $e) {
            return sendResponse(
                status: false,
                message: __('common.error'),
                data: null,
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function store(StoreCertificateRequest $request)
    {
        $validated = $request->validated();

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = CertificateStatus::VALID->value;
        $validated['issue_date'] = Carbon::parse($validated['issue_date']);
        $validated['expiry_date'] = Carbon::parse($validated['expiry_date']);

        // Handle file upload
        if (isset($validated['certificate_pdf']) && $validated['certificate_pdf'] instanceof UploadedFile) {
            $file = $validated['certificate_pdf'];
            $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('certificate_pdf', $fileName, 'public');
            $validated['certificate_pdf'] = $filePath;
        }

        try {
            $certificate = $this->certificateService->create($validated);

            return sendResponse(
                status: true,
                message: __('common.created'),
                data: new CertificateResourece($certificate),
                statusCode: HttpStatus::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return sendResponse(
                status: false,
                message: __('common.error'),
                data: null,
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function update(UpdateCertificateRequest $req, $id)
    {

        $validated = $req->validated();



        try {

            $certificate = $this->certificateService->find($id);


            if (!$certificate) {
                return sendResponse(
                    status: false,
                    message: __('common.not_found'),
                    data: null,
                    statusCode: HttpStatus::HTTP_NOT_FOUND
                );
            }

            if ($certificate->user_id !== $req->user()->id) {
                return sendResponse(
                    status: false,
                    message: __('common.unauthorized'),
                    data: null,
                    statusCode: HttpStatus::HTTP_UNAUTHORIZED
                );
            }

            // Parse dates if present
            if (isset($validated['issue_date'])) {
                $validated['issue_date'] = Carbon::parse($validated['issue_date']);
            }
            if (isset($validated['expiry_date'])) {
                $validated['expiry_date'] = Carbon::parse($validated['expiry_date']);
            }

            // Handle file upload
            if (isset($validated['certificate_pdf']) && $validated['certificate_pdf'] instanceof UploadedFile) {
                $file = $validated['certificate_pdf'];
                $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('certificate_pdf', $fileName, 'public');
                $validated['certificate_pdf'] = $filePath;

                // Delete old file if exists
                if ($certificate->certificate_pdf && Storage::disk('public')->exists($certificate->certificate_pdf)) {
                    Storage::disk('public')->delete($certificate->certificate_pdf);
                }
            }

            $certificate->update($validated);

            $certificate = $certificate->fresh();

            $certificate->load('certificateType');
            return sendResponse(
                status: true,
                message: __('common.updated'),
                data: new CertificateResourece($certificate),
                statusCode: HttpStatus::HTTP_OK
            );
        } catch (\Exception $e) {
            return sendResponse(
                status: false,
                message: __('common.error') . ': ' . $e->getMessage(),
                data: null,
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function destroy(Request $request, $id)
    {

        try {
            $certificate = $this->certificateService->find($id);

            if (!$certificate) {
                return sendResponse(
                    status: false,
                    message: __('common.not_found'),
                    data: null,
                    statusCode: HttpStatus::HTTP_NOT_FOUND
                );
            }

            if ($certificate->user_id !== $request->user()->id) {
                return sendResponse(
                    status: false,
                    message: __('common.unauthorized'),
                    data: null,
                    statusCode: HttpStatus::HTTP_FORBIDDEN
                );
            }

            if ($certificate->certificate_pdf && Storage::disk('public')->exists($certificate->certificate_pdf)) {
                Storage::disk('public')->delete($certificate->certificate_pdf);
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
                status: false,
                message: __('common.error'),
                data: null,
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
