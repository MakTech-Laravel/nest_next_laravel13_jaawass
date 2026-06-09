<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\CertificateService;
use App\Http\Requests\Api\V1\Admin\IndexCertificaitonRequest;
use App\Filters\Api\V1\Admin\CertificateFilter;
use App\Http\Resources\Api\V1\Manufacturer\CertificateResourece;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class CertificationController extends Controller
{
    //
    public function __construct(private CertificateService $certificateService) {}

    public function stats(IndexCertificaitonRequest $req)
    {

        $expired = $this->certificateService->query()
            ->where('expiry_date', '<', Carbon::now())
            ->count();

        $valid = $this->certificateService->query()
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

    public function index(IndexCertificaitonRequest $req)
    {

        $certificates = CertificateFilter::apply($this->certificateService->query(), $req)->paginate(
            perPage: $req->perPage(),
            pageName: 'page',
            page: $req->page(),
        );


        return  sendResponse(
            status: true,
            message: __('common.success'),
            data: CertificateResourece::collection($certificates),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function deleteCertificate($certificationId)
    {

        try {

            $this->certificateService->delete($certificationId);
            return sendResponse(
                status: true,
                message: __('common.success'),
                statusCode: HttpStatus::HTTP_OK
            );
        } catch (\Exception $e) {
            return sendResponse(
                status: false,
                message: __('common.error'),
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
