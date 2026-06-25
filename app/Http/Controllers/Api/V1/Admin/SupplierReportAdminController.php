<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexSupplierReportRequest;
use App\Http\Requests\Api\V1\Admin\UpdateSupplierReportRequest;
use App\Http\Resources\Api\V1\Admin\SupplierReportAdminResource;
use App\Models\SupplierReport;
use App\Services\SupplierReport\SupplierReportService;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class SupplierReportAdminController extends Controller
{
    public function __construct(
        private readonly SupplierReportService $supplierReportService,
    ) {}

    public function index(IndexSupplierReportRequest $request)
    {
        $validated = $request->validated();

        $reports = $this->supplierReportService
            ->adminListQuery($validated)
            ->paginate((int) ($validated['per_page'] ?? 15))
            ->withQueryString();

        return sendResponse(
            status: true,
            message: __('supplier_report.admin_list_success'),
            data: SupplierReportAdminResource::collection($reports),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function show(SupplierReport $supplierReport)
    {
        $supplierReport->load([
            'reporter',
            'supplier.company',
            'assignee',
            'resolver',
            'statusLogs.admin',
        ]);

        return sendResponse(
            status: true,
            message: __('supplier_report.admin_show_success'),
            data: new SupplierReportAdminResource($supplierReport),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function update(UpdateSupplierReportRequest $request, SupplierReport $supplierReport)
    {
        $report = $this->supplierReportService->updateByAdmin(
            $supplierReport,
            $request->user(),
            $request->validated(),
        );

        return sendResponse(
            status: true,
            message: __('supplier_report.admin_updated'),
            data: new SupplierReportAdminResource($report),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}
