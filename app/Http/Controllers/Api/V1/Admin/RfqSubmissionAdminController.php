<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\RfqSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\RfqSubmissionAdminResource;
use App\Models\RfqSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class RfqSubmissionAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'max:120'],
            'status' => ['sometimes', 'string', Rule::in(RfqSubmissionStatus::values())],
        ]);

        $query = RfqSubmission::query()
            ->with([
                'buyer',
                'manufacturer.company',
                'product',
                'conversation',
            ])
            ->latest('id');

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['search'])) {
            $searchTerm = trim((string) $validated['search']);

            $query->where(function (Builder $builder) use ($searchTerm): void {
                $builder
                    ->where('rfq_number', 'like', "%{$searchTerm}%")
                    ->orWhereHas('buyer', function (Builder $buyerQuery) use ($searchTerm): void {
                        $buyerQuery
                            ->where('first_name', 'like', "%{$searchTerm}%")
                            ->orWhere('last_name', 'like', "%{$searchTerm}%")
                            ->orWhere('email', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('product', function (Builder $productQuery) use ($searchTerm): void {
                        $productQuery->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('manufacturer.company', function (Builder $companyQuery) use ($searchTerm): void {
                        $companyQuery->where('company_name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        $rfqs = $query
            ->paginate((int) ($validated['per_page'] ?? 15))
            ->withQueryString();

        return sendResponse(
            status: true,
            message: __('api.admin_rfqs_fetched_successfully'),
            data: RfqSubmissionAdminResource::collection($rfqs),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function show(RfqSubmission $rfq): JsonResponse
    {
        $rfq->load([
            'buyer',
            'manufacturer.company',
            'product',
            'conversation',
        ]);

        return sendResponse(
            status: true,
            message: __('api.admin_rfq_fetched_successfully'),
            data: new RfqSubmissionAdminResource($rfq),
            statusCode: HttpStatus::HTTP_OK
        );
    }
}
