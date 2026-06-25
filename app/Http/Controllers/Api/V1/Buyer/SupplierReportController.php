<?php

namespace App\Http\Controllers\Api\V1\Buyer;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Buyer\StoreSupplierReportRequest;
use App\Http\Resources\Api\V1\SupplierReportResource;
use App\Models\SupplierReport;
use App\Models\User;
use App\Services\SupplierReport\SupplierReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class SupplierReportController extends Controller
{
    public function __construct(
        private readonly SupplierReportService $supplierReportService,
    ) {}

    public function index(Request $request)
    {
        $reports = SupplierReport::query()
            ->where('reporter_id', $request->user()->id)
            ->with(['supplier.company'])
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return sendResponse(
            status: true,
            message: __('supplier_report.list_success'),
            data: SupplierReportResource::collection($reports),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function store(StoreSupplierReportRequest $request, int $manufacturer)
    {
        $supplierUser = User::query()
            ->where('id', $manufacturer)
            ->where('role', UserRole::MANUFACTURER->value)
            ->first();

        if ($supplierUser === null) {
            return sendResponse(
                status: false,
                message: __('supplier_report.supplier_not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        $report = $this->supplierReportService->create(
            reporter: $request->user(),
            supplier: $supplierUser,
            payload: $request->validated(),
        );

        return sendResponse(
            status: true,
            message: __('supplier_report.created'),
            data: new SupplierReportResource($report),
            statusCode: HttpStatus::HTTP_CREATED,
        );
    }

    public function canReport(Request $request, int $manufacturer)
    {
        try {
            $this->supplierReportService->assertCanReport($request->user(), $manufacturer);

            return sendResponse(
                status: true,
                message: __('supplier_report.can_report'),
                data: ['can_report' => true],
                statusCode: HttpStatus::HTTP_OK,
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return sendResponse(
                status: true,
                message: $exception->getMessage(),
                data: [
                    'can_report' => false,
                    'reason' => collect($exception->errors())->flatten()->first(),
                ],
                statusCode: HttpStatus::HTTP_OK,
            );
        }
    }
}
